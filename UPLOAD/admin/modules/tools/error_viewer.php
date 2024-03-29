<?php
if (!defined("IN_MYBB"))
{
    die("Direct access not allowed.");
}

if (!in_array($mybb->settings['errorlogmedium'], array('log', 'both')))
{
    $query = $db->simple_select('settings', 'gid', 'name="errorlogmedium"', array('limit' => 1));
    $gid = $db->fetch_field($query, 'gid');

    $lang->error_viewer_logging_disabled = $lang->sprintf($lang->error_viewer_logging_disabled, "index.php?module=config-settings&action=change&gid={$gid}");

    $page->add_breadcrumb_item($lang->error_viewer_error_log_page, "index.php?module=tools-error_viewer");
    $page->output_header($lang->error_viewer_error_log_page);
    $page->output_error($lang->error_viewer_logging_disabled);
    $page->output_footer();
    exit;
}

$error_file_frontend = MYBB_ROOT . "/" . $mybb->settings['errorloglocation'];
$error_file_backend = MYBB_ROOT . "/" . $config['admin_dir'] . "/" . $mybb->settings['errorloglocation'];

$sub_tabs = array(
    "frontend" => array(
        "title" => $lang->error_viewer_front_end,
        "link" => "index.php?module=tools-error_viewer&amp;location=frontend",
        "description" => $lang->error_viewer_front_end_desc
    ),
    "backend" => array(
        "title" => $lang->error_viewer_back_end,
        "link" => "index.php?module=tools-error_viewer&amp;location=backend",
        "description" => $lang->error_viewer_back_end_desc
    )
);

if ($mybb->input['action'] == "clear" && $mybb->request_method == "post")
{
    if (!empty($mybb->input['location']))
    {
        switch ($mybb->input['location'])
        {
            case "frontend":
                $file = $error_file_frontend;
                $log_admin_action = $lang->error_viewer_admin_log_frontend;
                break;
            case "backend":
                $file = $error_file_backend;
                $log_admin_action = $lang->error_viewer_admin_log_backend;
                break;
        }

        $ret = false;

        if (is_writeable($file))
        {
            $ret = file_put_contents($file, '', LOCK_EX);
        }

        if ($ret !== false)
        {
            log_admin_action($log_admin_action);
            flash_message($lang->sprintf($lang->error_viewer_logs_cleared, $log_admin_action), 'success');
        }
        else
        {
            flash_message($lang->error_viewer_clearing_failed, 'error');
        }

        admin_redirect("index.php?module=tools-error_viewer&amp;location={$mybb->input['location']}");
        unset($file);
    }
}

if (!$mybb->input['action'])
{
    $location = $mybb->get_input("location");
    $fileexists = false;
    if ($location == "backend")
    {
        $page->add_breadcrumb_item($lang->error_viewer_error_log_backend, "index.php?module=tools-error_viewer&amp;location=backend");
        $page->output_header($lang->error_viewer_error_log_backend);
        $page->output_nav_tabs($sub_tabs, 'backend');
        if (@file_exists($error_file_backend))
        {
            $fileexists = true;
            $filecontents = @file_get_contents($error_file_backend);
        }
    }
    else
    {
        $page->add_breadcrumb_item($lang->error_viewer_error_log_frontend, "index.php?module=tools-error_viewer");
        $page->output_header($lang->error_viewer_error_log_frontend);
        $page->output_nav_tabs($sub_tabs, 'frontend');
        if (@file_exists($error_file_frontend))
        {
            $fileexists = true;
            $filecontents = @file_get_contents($error_file_frontend);
            $location = "frontend";
        }
    }
    if (!isset($filecontents) && $fileexists !== true)
    {
        $page->output_error($lang->error_viewer_file_not_found);
        $page->output_footer();
        exit;
    }

    $entries = explode("\n\n", $filecontents);
    $itemcount = count($entries);
    $last = $itemcount - 1;
    unset($entries[$last]);
    if (!empty($mybb->input['page']))
    {
        $current_page = $mybb->get_input("page", MyBB::INPUT_INT);
    }
    else
    {
        $current_page = 1;
    }
    if (!$current_page)
    {
        $current_page = 1;
    }
    $pages = ceil($last / 50);
    if ($current_page > $pages)
    {
        $current_page = $pages;
    }
    $start = $current_page * 50 - 50;
    $pagination = draw_admin_pagination($current_page, 50, $last, "index.php?module=tools-error_viewer&amp;location=" . $location);

    /* Flip the array so the newest errors are shown first since that is usually what admins want. */
    $entries = array_reverse($entries);
    $error_array = array_slice($entries, $start, 50);

    echo $pagination;

    $table = new TABLE;
    $table->construct_header($lang->error_viewer_date, array("class" => "align_center", 'width' => '10%'));
    $table->construct_header($lang->error_viewer_file, array('width' => '20%'));
    $table->construct_header($lang->error_viewer_line, array("class" => "align_center", 'width' => '5%'));
    $table->construct_header($lang->error_viewer_type, array("class" => "align_center", 'width' => '10%'));
    $table->construct_header($lang->error_viewer_message);
    $table->construct_row();
    if (!empty($error_array))
    {
        foreach ($error_array as $entry)
        {
            $back_trace = '';
            if (function_exists('debug_backtrace') && $mybb->version_code >= 1820)
            {
                $back_trace = "\t<back_trace>(.*)<\/back_trace>\n";
            }

            $string = array();

            preg_match_all('/\A<error>\n'
                . '\t<dateline>([0-9]+)<\/dateline>\n'
                . '\t<script>(.*)<\/script>\n'
                . '\t<line>([0-9]+)<\/line>\n'
                . '\t<type>([0-9]+)<\/type>\n'
                . '\t<friendly_type>(.*)<\/friendly_type>\n'
                . '\t<message>(.*)<\/message>\n'
                . $back_trace
                . '<\/error>(.*?)\Z/is', $entry, $string);

            if (empty($string))
            {
                continue;
            }
            else
            {
                $date = my_date('relative', (int)$string[1][0]);
                $filename = htmlspecialchars_uni($string[2][0]);
                $line = $string[3][0] != 0 ? $string[3][0] : '-';
                $friendly_type = $string[5][0];
                $message = nl2br($string[6][0]);
            }

            $table->construct_cell($date, array("class" => "align_center"));
            $table->construct_cell($filename);
            $table->construct_cell($line, array("class" => "align_center"));
            $table->construct_cell($friendly_type, array("class" => "align_center"));
            $table->construct_cell($message);
            $table->construct_row();

            unset($string);
        }
    }
    else
    {
        $table->construct_cell($lang->error_viewer_no_entries, array('colspan' => '5'));
        $table->construct_row();
    }

    $table->output($lang->error_viewer_errors_warnings);

    echo $pagination;

    if (!empty($error_array) && $fileexists !== false)
    {
        $error_viewer_clear_log_confirm = "error_viewer_clear_{$location}_log_confirm";

        $form = new Form("index.php?module=tools-error_viewer&amp;action=clear&amp;location={$location}", "post");
        $buttons = array();
        $buttons[] = $form->generate_submit_button($lang->error_viewer_clear_log, array('name' => 'clear', 'onclick' => "return confirm('{$lang->$error_viewer_clear_log_confirm}');"));
        $form->output_submit_wrapper($buttons);
        $form->end();
    }

    $page->output_footer();
}

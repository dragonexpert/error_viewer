<?php
if(!defined("IN_MYBB"))
{
    die("Direct access not allowed.");
}

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

$location = $mybb->get_input("location");
if($location == "backend")
{
    $page->add_breadcrumb_item($lang->error_viewer_error_log_backend, "index.php?module=tools-error_viewer&amp;location=backend");
    $page->output_header($lang->error_viewer_error_log_backend);
    $page->output_nav_tabs($sub_tabs, 'backend');
    if(@file_exists(MYBB_ROOT . "/" . $config['admin_dir'] . "/" . $mybb->settings['errorloglocation']))
    {
        $filecontents = @file_get_contents(MYBB_ROOT . "/" . $config['admin_dir'] . "/" . $mybb->settings['errorloglocation']);
    }
}
else
{
    $page->add_breadcrumb_item($lang->error_viewer_error_log_frontend, "index.php?module=tools-error_viewer");
    $page->output_header($lang->error_viewer_error_log_frontend);
    $page->output_nav_tabs($sub_tabs, 'frontend');
    if(@file_exists(MYBB_ROOT . "/" . $mybb->settings['errorloglocation']))
    {
        $filecontents = @file_get_contents(MYBB_ROOT . "/" . $mybb->settings['errorloglocation']);
        $location = "frontend";
    }
}
if(!$filecontents)
{
    $page->output_error($lang->error_viewer_file_not_found);
    $page->output_footer();
    exit;
}

preg_match_all('/\<error\>(.*?)\<\/error\>/is', $filecontents, $matches);

$entries = $matches[0];
$itemcount = count($entries);
$last = $itemcount - 1;
//unset($entries[$last]);
if($mybb->input['page'])
{
    $current_page = $mybb->get_input("page", MyBB::INPUT_INT);
}
else
{
    $current_page = 1;
}
if(!$current_page)
{
    $current_page = 1;
}
$pages = ceil($last / 50);
if($current_page > $pages)
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
foreach($error_array as $entry)
{
    preg_match_all('/<dateline>([0-9]+)<\/dateline>|<script>(.*)<\/script>|<line>([0-9]+)<\/line>|<friendly_type>(.*)<\/friendly_type>|<message>(.*)<\/message>/is', $entry, $string);
    $date = my_date('relative', $string[1][0]);
    $filename = $string[2][1];
    $line = $string[3][2];
    if(!$line)
    {
        $line = '-';
    }
    $friendly_type = $string[4][3];
    $message = nl2br($string[5][4]);

    $table->construct_cell($date, array("class" => "align_center"));
    $table->construct_cell($filename);
    $table->construct_cell($line, array("class" => "align_center"));
    $table->construct_cell($friendly_type, array("class" => "align_center"));
    $table->construct_cell($message);
    $table->construct_row();
}
$table->output($lang->error_viewer_errors_warnings);

echo $pagination;

$page->output_footer();

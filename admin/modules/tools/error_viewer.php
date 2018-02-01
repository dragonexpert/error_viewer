<?php
if(!defined("IN_MYBB"))
{
    die("Direct access not allowed.");
}
$page->add_breadcrumb_item($lang->error_viewer_error_log, "index.php?module=tools-error_viewer");
$page->output_header($lang->error_viewer_error_log);
$baseurl = "index.php?module=tools-error_viewer";
$table = new TABLE;
$location = $mybb->get_input("location");
if($location == "admin")
{
    if(file_exists(MYBB_ROOT . "/" . $config['admin_dir'] . "/" . $mybb->settings['errorloglocation']))
    {
        $filecontents = file_get_contents(MYBB_ROOT . "/" . $config['admin_dir'] . "/" . $mybb->settings['errorloglocation']);
    }
    else
    {
        $page->output_error($lang->error_viewer_file_not_found);
        $page->output_footer();
    }
}
else
{
    if(file_exists(MYBB_ROOT . "/" . $mybb->settings['errorloglocation']))
    {
        $filecontents = file_get_contents(MYBB_ROOT . "/" . $mybb->settings['errorloglocation']);
        $location = "user";
    }
    else
    {
        $page->output_error($lang->error_viewer_file_not_found);
        $page->output_footer();
    }
}

$sub_tabs = array(
    "user" => array(
        "title" => $lang->error_viewer_front_end,
        "link" => $baseurl . "&location=user"
    ),
    "admin" => array(
        "title" => $lang->error_viewer_back_end,
        "link" => $baseurl . "&location=admin"
    )
);

$page->output_nav_tabs($sub_tabs);

$entries = explode("\n\n", $filecontents);
$itemcount = count($entries);
$last = $itemcount - 1;
unset($entries[$last]);
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
$pagination = draw_admin_pagination($current_page, 50, $last, "index.php?module=tools-error_viewer&location=" . $location);

/* Flip the array so the newest errors are shown first since that is usually what admins want. */
$entries = array_reverse($entries);
$error_array = array_slice($entries, $start, 50);

echo $pagination;

$table->construct_header($lang->error_viewer_date);
$table->construct_header($lang->error_viewer_time);
$table->construct_header($lang->error_viewer_file);
$table->construct_header($lang->error_viewer_line);
$table->construct_header($lang->error_viewer_type);
$table->construct_header($lang->error_viewer_message);
$table->construct_row();
foreach($error_array as $entry)
{
    $string = preg_replace("/\A<error>\n\t<dateline>([0-9]+)<\/dateline>\n\t<script>(.*)<\/script>\n\t<line>([0-9]+)<\/line>\n\t<type>([0-9]+)<\/type>" .
        "\n\t<friendly_type>(.*)<\/friendly_type>\n\t<message>(.*)<\/message>\n<\/error>(.*?)\Z/is", "$1--$2--$3--$4--$5--$6", $entry);
    $exstring = explode("--", $string);
    $date = my_date($mybb->settings['dateformat'], $exstring[0]);
    $time = my_date($mybb->settings['timeformat'], $exstring[0]);
    $filename = $exstring[1];
    $line = $exstring[2];
    $friendly_type = $exstring[4];
    $message = nl2br($exstring[5]);
    $table->construct_cell($date);
    $table->construct_cell($time);
    $table->construct_cell($filename);
    $table->construct_cell($line);
    $table->construct_cell($friendly_type);
    $table->construct_cell($message);
    $table->construct_row();
}
$table->output($lang->error_viewer_errors_warnings);
echo $pagination;
$page->output_footer();

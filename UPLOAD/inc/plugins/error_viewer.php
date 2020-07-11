<?php
if(!defined("IN_MYBB"))
{
    die("Direct access not allowed.");
}

$plugins->add_hook("admin_tools_menu_logs", "error_viewer_admin_tools_menu_logs");
$plugins->add_hook("admin_tools_action_handler", "error_viewer_admin_tools_action_handler");
$plugins->add_hook("admin_tools_permissions", "error_viewer_admin_tools_permissions");

function error_viewer_info()
{
    return array(
        "name"	=> "Error Viewer",
        "description" => "Allows you to view errors in the tools section.",
        "author" => "Mark Janssen - modified by SvePu",
        "version" => "1.1",
        "codename" 	=> "error_viewer",
        "compatibility"	=> "18*"
    );
}

function error_viewer_activate()
{

}

function error_viewer_deactivate()
{

}

function error_viewer_admin_tools_menu_logs(&$sub_menu)
{
    global $lang;
    $lang->load("error_viewer");
    $sub_menu[77] = array(
        "id" => "error_viewer",
        "title" => $lang->error_viewer_error_log,
        "link" => "index.php?module=tools-error_viewer"
    );
}

function error_viewer_admin_tools_action_handler(&$actions)
{
    $actions['error_viewer'] = array(
        "active" => "error_viewer",
        "file" => "error_viewer.php"
    );
}

function error_viewer_admin_tools_permissions(&$admin_permissions)
{
    global $lang;
    $lang->load("error_viewer");
    $admin_permissions['error_viewer'] = $lang->error_viewer_can_view;
}

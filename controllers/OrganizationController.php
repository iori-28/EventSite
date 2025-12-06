<?php
require_once $_SERVER['DOCUMENT_ROOT'] . '/EventSite/models/Organization.php';

class OrganizationController
{

    public static function create($name, $desc, $user_id)
    {
        return Organization::create($name, $desc, $user_id);
    }

    public static function pending()
    {
        return Organization::getPending();
    }

    public static function approve($id)
    {
        return Organization::approve($id);
    }

    public static function reject($id)
    {
        return Organization::reject($id);
    }
}

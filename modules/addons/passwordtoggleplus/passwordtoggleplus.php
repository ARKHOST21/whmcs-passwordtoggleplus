<?php

if (!defined("WHMCS")) {
    die("This file cannot be accessed directly");
}

function passwordtoggleplus_config() {
    return [
        'name' => 'Password Toggle Plus',
        'description' => 'Enhanced password visibility control with copy functionality for WHMCS product details',
        'version' => '1.0',
        'author' => '<a href="https://arkhost.com/" target="_blank">ArkHost</a>',
        'fields' => []
    ];
}

function passwordtoggleplus_activate() {
    return [
        'status' => 'success',
        'description' => 'Password Toggle Plus activated successfully'
    ];
}

function passwordtoggleplus_deactivate() {
    return [
        'status' => 'success',
        'description' => 'Password Toggle Plus deactivated successfully'
    ];
}

function passwordtoggleplus_clientarea($vars) {
    if (isset($_GET['action']) && $_GET['action'] === 'getpassword') {
        header('Content-Type: text/plain');
        
        $serviceid = (int)$_GET['id'];
        $pid = Capsule::table('tblhosting')
            ->where('id', $serviceid)
            ->value('packageid');
            
        if ($pid) {
            $fields = Capsule::table('tblcustomfields')
                ->where('type', 'product')
                ->where('relid', $pid)
                ->get();
                
            foreach ($fields as $field) {
                if (strpos(strtolower($field->fieldname), 'password') !== false) {
                    $value = Capsule::table('tblcustomfieldsvalues')
                        ->where('fieldid', $field->id)
                        ->where('relid', $serviceid)
                        ->value('value');
                        
                    logActivity("Password Toggle Plus - Password request via clientarea");
                    return ['password' => $value ?? 'No password value'];
                }
            }
        }
        return ['password' => 'No password found'];
    }
    return [];
}

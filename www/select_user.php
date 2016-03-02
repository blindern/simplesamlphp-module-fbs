<?php

$globalConfig = SimpleSAML_Configuration::getInstance();

SimpleSAML_Logger::info('FBS - UKA Google Apps user selection: Accessing interface');

if (!isset($_REQUEST['StateId'])) {
    throw new SimpleSAML_Error_BadRequest(
        'Missing required StateId query parameter.'
    );
}

$id = $_REQUEST['StateId'];

// sanitize the input
$sid = SimpleSAML_Utilities::parseStateID($id);
if (!is_null($sid['url'])) {
    SimpleSAML_Utilities::checkURLAllowed($sid['url']);
}

$state = SimpleSAML_Auth_State::loadState($id, 'fbs:request');

$usernames = $state['fbs:usernames'];

//var_dump($state);die;

if (isset($_POST['username'])) {
    $selected = null;
    foreach ($usernames as $username) {
        if ($username == $_POST['username']) {
            $selected = $username;
            break;
        }
    }

    if (!is_null($selected)) {
        $state['Attributes']['username'] = array($selected);
        SimpleSAML_Auth_ProcessingChain::resumeProcessing($state);
    }
}

// Make, populate and layout consent form
$t = new SimpleSAML_XHTML_Template($globalConfig, 'fbs:select_user.php');
$t->data['srcMetadata'] = $state['Source'];
$t->data['dstMetadata'] = $state['Destination'];
$t->data['formAction'] = SimpleSAML_Module::getModuleURL('fbs/select_user.php');
$t->data['formData'] = array('StateId' => $id);
$t->data['attributes'] = $state['Attributes'];
$t->data['usernames'] = $usernames;

$t->show();

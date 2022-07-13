<?php

$globalConfig = SimpleSAML\Configuration::getInstance();

SimpleSAML\Logger::info('FBS - UKA Google Apps user selection: Accessing interface');

if (!isset($_REQUEST['StateId'])) {
    throw new SimpleSAML\Error\BadRequest(
        'Missing required StateId query parameter.'
    );
}

$id = $_REQUEST['StateId'];

// sanitize the input
$sid = \SimpleSAML\Auth\State::parseStateID($id);
if (!is_null($sid['url'])) {
    \SimpleSAML\Utils\HTTP::checkURLAllowed($sid['url']);
}

$state = SimpleSAML\Auth\State::loadState($id, 'fbs:request');

$usernames = $state['fbs:usernames'];

if (isset($_POST['username'])) {
    $selected = null;
    foreach ($usernames as $username) {
        if ($username == $_POST['username']) {
            $selected = $username;
            break;
        }
    }

    if (!is_null($selected)) {
        if (strpos($selected, "@") === false) {
            $selected .= "@blindernuka.no";
        }
        $state['Attributes']['gapps-mail'] = array($selected);
        SimpleSAML\Auth\ProcessingChain::resumeProcessing($state);
    }
}

// Make, populate and layout consent form
$t = new SimpleSAML\XHTML\Template($globalConfig, 'fbs:select_user.php');
$t->data['srcMetadata'] = $state['Source'];
$t->data['dstMetadata'] = $state['Destination'];
$t->data['formAction'] = SimpleSAML\Module::getModuleURL('fbs/select_user.php');
$t->data['formData'] = array('StateId' => $id);
$t->data['attributes'] = $state['Attributes'];
$t->data['usernames'] = $usernames;

$t->show();

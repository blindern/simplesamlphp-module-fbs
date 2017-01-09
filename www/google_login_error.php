<?php

$globalConfig = SimpleSAML_Configuration::getInstance();

SimpleSAML_Logger::info('FBS - Google Account error');

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

$idp = SimpleSAML_IdP::getByState($state);
if ($idp->isAuthenticated()) {
    $id  = SimpleSAML_Auth_State::saveState($state, 'fbs:request');
    $url = SimpleSAML_Module::getModuleURL('fbs/google_login_error.php');
    $newState = array(
        'Responder'       => array('sspmod_fbs_Auth_Process_GoogleAccount', 'finishLogoutRedirect'),
        'core:Logout:URL' => $url,
        'fbs:usernames'   => $state['fbs:usernames'],
        'Attributes'      => $state['Attributes'],
    );

    $idp->handleLogoutRequest($newState, null);
}

$t = new SimpleSAML_XHTML_Template($globalConfig, 'fbs:google_login_error.php');
$t->data['attributes'] = $state['Attributes'];
$t->data['usernames'] = $state['fbs:usernames'];

$t->show();

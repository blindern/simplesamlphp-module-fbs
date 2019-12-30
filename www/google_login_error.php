<?php

$globalConfig = \SimpleSAML\Configuration::getInstance();

\SimpleSAML\Logger::info('FBS - Google Account error');

if (!isset($_REQUEST['StateId'])) {
    throw new \SimpleSAML\Error\BadRequest(
        'Missing required StateId query parameter.'
    );
}

$id = $_REQUEST['StateId'];

// sanitize the input
$sid = \SimpleSAML\Utilities::parseStateID($id);
if (!is_null($sid['url'])) {
    \SimpleSAML\Utilities::checkURLAllowed($sid['url']);
}

$state = \SimpleSAML\Auth\State::loadState($id, 'fbs:request');

$idp = \SimpleSAML\IdP::getByState($state);
if ($idp->isAuthenticated()) {
    $id  = \SimpleSAML\Auth\State::saveState($state, 'fbs:request');
    $url = \SimpleSAML\Module::getModuleURL('fbs/google_login_error.php');
    $newState = array(
        'Responder'       => array('\\SimpleSAML\\Module\\fbs\\Auth\\Process\\GoogleAccount', 'finishLogoutRedirect'),
        'core:Logout:URL' => $url,
        'fbs:usernames'   => $state['fbs:usernames'],
        'Attributes'      => $state['Attributes'],
    );

    $idp->handleLogoutRequest($newState, null);
}

$t = new \SimpleSAML\XHTML\Template($globalConfig, 'fbs:google_login_error.php');
$t->data['attributes'] = $state['Attributes'];
$t->data['usernames'] = $state['fbs:usernames'];

$t->show();

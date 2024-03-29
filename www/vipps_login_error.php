<?php

$globalConfig = \SimpleSAML\Configuration::getInstance();

\SimpleSAML\Logger::info('FBS - Vipps error');

if (!isset($_REQUEST['StateId'])) {
    throw new \SimpleSAML\Error\BadRequest(
        'Missing required StateId query parameter.'
    );
}

$id = $_REQUEST['StateId'];

// sanitize the input
$sid = \SimpleSAML\Auth\State::parseStateID($id);
if (!is_null($sid['url'])) {
    \SimpleSAML\Utils\HTTP::checkURLAllowed($sid['url']);
}

$state = \SimpleSAML\Auth\State::loadState($id, 'fbs:request');

$idp = \SimpleSAML\IdP::getByState($state);
if ($idp->isAuthenticated()) {
    $id  = \SimpleSAML\Auth\State::saveState($state, 'fbs:request');
    $url = \SimpleSAML\Module::getModuleURL('fbs/vipps_login_error.php');
    $newState = array(
        'Responder'       => array('\\SimpleSAML\\Module\\fbs\\Auth\\Process\\Vipps', 'finishLogoutRedirect'),
        'core:Logout:URL' => $url,
        'fbs:usernames'   => $state['fbs:usernames'],
        'fbs:email'       => $state['fbs:email'],
        'fbs:phoneNumber' => $state['fbs:phoneNumber'],
        'Attributes'      => $state['Attributes'],
    );

    $idp->handleLogoutRequest($newState, null);
}

$t = new \SimpleSAML\XHTML\Template($globalConfig, 'fbs:vipps_login_error.php');
$t->data['email'] = $state['fbs:email'];
$t->data['phoneNumber'] = $state['fbs:phoneNumber'];
$t->data['usernames'] = $state['fbs:usernames'];

$t->show();

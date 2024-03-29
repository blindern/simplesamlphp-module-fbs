<?php

namespace SimpleSAML\Module\fbs\Controller;

use SimpleSAML\Auth;
use SimpleSAML\Configuration;
use SimpleSAML\Error;
use SimpleSAML\IdP;
use SimpleSAML\Logger;
use SimpleSAML\Module;
use SimpleSAML\Session;
use SimpleSAML\XHTML\Template;
use Symfony\Component\HttpFoundation\Request;

class FbsController
{
    /** @var \SimpleSAML\Configuration */
    protected $config;

    /** @var \SimpleSAML\Session */
    protected $session;

    /**
     * @var \SimpleSAML\Auth\State|string
     * @psalm-var \SimpleSAML\Auth\State|class-string
     */
    protected $authState = Auth\State::class;

    /**
     * @var \SimpleSAML\Logger|string
     * @psalm-var \SimpleSAML\Logger|class-string
     */
    protected $logger = Logger::class;


    /**
     * ConsentController constructor.
     *
     * @param \SimpleSAML\Configuration $config The configuration to use.
     * @param \SimpleSAML\Session $session The current user session.
     */
    public function __construct(Configuration $config, Session $session)
    {
        $this->config = $config;
        $this->session = $session;
    }


    /**
     * Inject the \SimpleSAML\Auth\State dependency.
     *
     * @param \SimpleSAML\Auth\State $authState
     */
    public function setAuthState(Auth\State $authState): void
    {
        $this->authState = $authState;
    }


    /**
     * Inject the \SimpleSAML\Logger dependency.
     *
     * @param \SimpleSAML\Logger $logger
     */
    public function setLogger(Logger $logger): void
    {
        $this->logger = $logger;
    }


    public function google_login_error(Request $request)
    {
        $this->logger::info('FBS - Google Account error');

        $stateId = $request->query->get('StateId');
        if ($stateId === null) {
            throw new Error\BadRequest('Missing required StateId query parameter.');
        }

        $state = $this->authState::loadState($stateId, 'fbs:request');

        if (is_null($state)) {
            throw new Error\NoState();
        }

        $idp = IdP::getByState($state);
        if ($idp->isAuthenticated()) {
            //$id  = \SimpleSAML\Auth\State::saveState($state, 'fbs:request');
            $url = Module::getModuleURL('fbs/google-login-error');
            $newState = array(
                'Responder'       => [\SimpleSAML\Module\fbs\Auth\Process\GoogleAccount::class, 'finishLogoutRedirect'],
                'core:Logout:URL' => $url,
                'fbs:email'       => $state['fbs:email'],
                'fbs:usernames'   => $state['fbs:usernames'],
                'Attributes'      => $state['Attributes'],
            );

            $idp->handleLogoutRequest($newState, null);
        }

        $t = new Template($this->config, 'fbs:google_login_error.twig');
        $t->data['email'] = $state['fbs:email'];
        $t->data['usernames'] = $state['fbs:usernames'];

        return $t;
    }


    public function vipps_login_error(Request $request)
    {
        $this->logger::info('FBS - Vipps error');

        $stateId = $request->query->get('StateId');
        if ($stateId === null) {
            throw new Error\BadRequest('Missing required StateId query parameter.');
        }

        $state = $this->authState::loadState($stateId, 'fbs:request');

        if (is_null($state)) {
            throw new Error\NoState();
        }

        $idp = IdP::getByState($state);
        if ($idp->isAuthenticated()) {
            //$id  = \SimpleSAML\Auth\State::saveState($state, 'fbs:request');
            $url = Module::getModuleURL('fbs/vipps-login-error');
            $newState = array(
                'Responder'       => [\SimpleSAML\Module\fbs\Auth\Process\Vipps::class, 'finishLogoutRedirect'],
                'core:Logout:URL' => $url,
                'fbs:usernames'   => $state['fbs:usernames'],
                'fbs:email'       => $state['fbs:email'],
                'fbs:phoneNumber' => $state['fbs:phoneNumber'],
                'Attributes'      => $state['Attributes'],
            );

            $idp->handleLogoutRequest($newState, null);
        }

        $t = new Template($this->config, 'fbs:vipps_login_error.twig');
        $t->data['email'] = $state['fbs:email'];
        $t->data['phoneNumber'] = $state['fbs:phoneNumber'];
        $t->data['usernames'] = $state['fbs:usernames'];

        return $t;
    }


    public function select_user(Request $request)
    {
        $this->logger::info('FBS - UKA Google Apps user selection: Accessing interface');

        $stateId = $request->query->get('StateId');
        if ($stateId === null) {
            throw new Error\BadRequest('Missing required StateId query parameter.');
        }

        $state = $this->authState::loadState($stateId, 'fbs:request');

        if (is_null($state)) {
            throw new Error\NoState();
        }

        $usernames = $state['fbs:usernames'];

        $post_username = $request->request->get('username');
        if (!is_null($post_username)) {
            $selected = null;
            foreach ($usernames as $username) {
                if ($username == $post_username) {
                    $selected = $username;
                    break;
                }
            }

            if (!is_null($selected)) {
                if (strpos($selected, "@") === false) {
                    $selected .= "@blindernuka.no";
                }
                $state['Attributes']['gapps-mail'] = array($selected);
                \SimpleSAML\Auth\ProcessingChain::resumeProcessing($state);
            }
        }

        $t = new Template($this->config, 'fbs:select_user.twig');
        $t->data['StateId'] = $stateId;
        $t->data['realname'] = $state['Attributes']['realname'][0];
        $t->data['username'] = $state['Attributes']['username'][0];
        $t->data['usernames'] = $usernames;

        return $t;
    }
}

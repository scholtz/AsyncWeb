<?php
namespace AsyncWeb\Storage;
use AsyncWeb\Storage\Session;
use OAuth\Common\Token\TokenInterface;
use OAuth\Common\Storage\TokenStorageInterface;
use OAuth\Common\Storage\Exception\TokenNotFoundException;
/**
 * All token storage providers must implement this interface.
 */
class OAuthLibSession implements TokenStorageInterface {
    /**
     * @var string
     */
    protected $sessionVariableName;
    /**
     * @var string
     */
    protected $stateVariableName;
    /**
     * @param bool $startSession Whether or not to start the session upon construction.
     * @param string $sessionVariableName the variable name to use within the _SESSION superglobal
     * @param string $stateVariableName
     */
    public function __construct($sessionVariableName = 'lusitanian_oauth_token', $stateVariableName = 'lusitanian_oauth_state') {
        $this->sessionVariableName = $sessionVariableName;
        $this->stateVariableName = $stateVariableName;
    }
    /**
     * {@inheritDoc}
     */
    public function retrieveAccessToken($service) {
        if ($this->hasAccessToken($service)) {
            $var = Session::get($this->sessionVariableName);
            if ($var && isset($var[$service])) {
                return unserialize($var[$service]);
            }
        }
        throw new TokenNotFoundException('Token not found in session, are you sure you stored it?');
    }
    /**
     * {@inheritDoc}
     */
    public function storeAccessToken($service, TokenInterface $token) {
        $serializedToken = serialize($token);
        $var = Session::get($this->sessionVariableName);
        if ($var && is_array($var)) {
            $var[$service] = $serializedToken;
            Session::set($this->sessionVariableName, $var);
        } else {
            Session::set($this->sessionVariableName, array($service => $serializedToken,));
        }
        // allow chaining
        return $this;
    }
    /**
     * {@inheritDoc}
     */
    public function hasAccessToken($service) {
        $var = Session::get($this->sessionVariableName);
        return isset($var[$service]);
    }
    /**
     * {@inheritDoc}
     */
    public function clearToken($service) {
        $var = Session::get($this->sessionVariableName);
        if (array_key_exists($service, $var)) {
            unset($var[$service]);
            Session::set($this->sessionVariableName, $var);
        }
        // allow chaining
        return $this;
    }
    /**
     * {@inheritDoc}
     */
    public function clearAllTokens() {
        Session::set($this->sessionVariableName, array());
        // allow chaining
        return $this;
    }
    /**
     * {@inheritDoc}
     */
    public function storeAuthorizationState($service, $state) {
        $var = Session::get($this->stateVariableName);
        if (isset($var[$service])) {
            $var[$service] = $state;
            Session::set($this->stateVariableName, $var);
        } else {
            Session::set($this->stateVariableName, array($service => $state,));
        }
        // allow chaining
        return $this;
    }
    /**
     * {@inheritDoc}
     */
    public function hasAuthorizationState($service) {
        $var = Session::get($this->stateVariableName);
        return isset($var[$service]);
    }
    /**
     * {@inheritDoc}
     */
    public function retrieveAuthorizationState($service) {
        if ($this->hasAuthorizationState($service)) {
            $var = Session::get($this->stateVariableName);
            return $var[$service];
        }
        throw new AuthorizationStateNotFoundException('State not found in session, are you sure you stored it?');
    }
    /**
     * {@inheritDoc}
     */
    public function clearAuthorizationState($service) {
        $var = Session::get($this->stateVariableName);
        if (array_key_exists($service, $var)) {
            unset($var[$service]);
            Session::set($this->stateVariableName, $var);
        }
        // allow chaining
        return $this;
    }
    /**
     * {@inheritDoc}
     */
    public function clearAllAuthorizationStates() {
        Session::set($this->stateVariableName, array());
        // allow chaining
        return $this;
    }
}

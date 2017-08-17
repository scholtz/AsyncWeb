<?php

namespace AsyncWeb\Security\Service;

use OAuth\OAuth2\Token\StdOAuth2Token;
use OAuth\Common\Http\Exception\TokenResponseException;
use OAuth\Common\Http\Uri\Uri;
use OAuth\Common\Consumer\CredentialsInterface;
use OAuth\Common\Http\Client\ClientInterface;
use OAuth\Common\Storage\TokenStorageInterface;
use OAuth\Common\Http\Uri\UriInterface;

class Azure extends \OAuth\OAuth2\Service\AbstractService
{
    const SCOPE_EMAIL = 'email';
    const SCOPE_OFFLINE = 'offline_access';
    const SCOPE_PROFILE = 'profile';
    const SCOPE_OPENID = 'openid';
	const SCOPE_GRAPH_MAIL_READ = 'https://graph.microsoft.com/mail.read';
	
	const SCOPE_GRAPH_USER_READ = 'https://graph.microsoft.com/User.Read';
	const SCOPE_GRAPH_USER_READ_ALL = 'https://graph.microsoft.com/User.Read.All';
	
	
    /**
     *
	 * https://docs.microsoft.com/en-us/azure/active-directory/develop/active-directory-protocols-oauth-code
	 * https://developer.microsoft.com/en-us/graph/docs/authorization/permission_scopes
	 *
     */

    public function __construct(
        CredentialsInterface $credentials,
        ClientInterface $httpClient,
        TokenStorageInterface $storage,
        $scopes = array(),
        UriInterface $baseApiUri = null
    ) {
		
        parent::__construct($credentials, $httpClient, $storage, $scopes, $baseApiUri);

		
        if (null === $baseApiUri) {
            $this->baseApiUri = new Uri('https://graph.windows.net/common/');
        }
    }

    /**
     * {@inheritdoc}
     */
    public function getAuthorizationEndpoint()
    {
        return new Uri('https://login.microsoftonline.com/common/oauth2/authorize');
    }

    /**
     * {@inheritdoc}
     */
    public function getAccessTokenEndpoint()
    {
        return new Uri('https://login.microsoftonline.com/common/oauth2/token');
    }

    /**
     * {@inheritdoc}
     */
    public function getAuthorizationMethod()
    {
        return static::AUTHORIZATION_METHOD_QUERY_STRING;
    }

    /**
     * {@inheritdoc}
     */
    protected function parseAccessTokenResponse($responseBody)
    {
		
        $data = json_decode($responseBody, true);
		

        if (null === $data || !is_array($data)) {
            throw new TokenResponseException('Unable to parse response.');
        } elseif (isset($data['error'])) {
            throw new TokenResponseException('Error in retrieving token: "' . $data['error'] . '"');
        }

        $token = new StdOAuth2Token();
        $token->setAccessToken($data['access_token']);
        $token->setLifetime($data['expires_in']);

        if (isset($data['refresh_token'])) {
            $token->setRefreshToken($data['refresh_token']);
            unset($data['refresh_token']);
        }

        unset($data['access_token']);
        unset($data['expires_in']);

		
        $token->setExtraParams($data);

			
		
		
        return $token;
    }
	
	public $email = "";

    /**
     * Sends an authenticated API request to the path provided.
     * If the path provided is not an absolute URI, the base API Uri (must be passed into constructor) will be used.
     *
     * @param string|UriInterface $path
     * @param string              $method       HTTP method
     * @param array               $body         Request body if applicable.
     * @param array               $extraHeaders Extra headers if applicable. These will override service-specific
     *                                          any defaults.
     *
     * @return string
     *
     * @throws ExpiredTokenException
     * @throws Exception
     */
    public function request($path, $method = 'GET', $body = null, array $extraHeaders = array())
    {
        $uri = $this->determineRequestUriFromPath($path, $this->baseApiUri);
        $data = $this->storage->retrieveAccessToken($this->service())->getExtraParams();
		$d = base64_decode($data["id_token"]);
		$arr = explode("}",$d);
		$type = json_decode($arr["1"]."}",true);
		$tokendata = json_decode($arr["1"]."}",true);
		
		if(!$tokendata["email"]){
			if(\AsyncWeb\Text\Validate::check("email",$tokendata["unique_name"])){
				$tokendata["email"] = $tokendata["unique_name"];
			}
		}
		if(!$tokendata["email"]){
			if(\AsyncWeb\Text\Validate::check("email",$tokendata["upn"])){
				$tokendata["email"] = $tokendata["upn"];
			}
		}
        return json_encode($tokendata);
    }

}

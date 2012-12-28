<?php
/**
 * Concrete5 HTTP Basic Authentication
 *
 * Use this middleware with your Slim Framework application
 * to require HTTP basic auth for all routes, using Concrete5 as an auth DB.
 *
 * @author Josh Lockhart <info@slimframework.com>
 * @author Oliver Green <green2go@gmail.com>
 * @version 1.0
 * @copyright 2012 Josh Lockhart
 *
 * MIT LICENSE
 *
 * Permission is hereby granted, free of charge, to any person obtaining
 * a copy of this software and associated documentation files (the
 * "Software"), to deal in the Software without restriction, including
 * without limitation the rights to use, copy, modify, merge, publish,
 * distribute, sublicense, and/or sell copies of the Software, and to
 * permit persons to whom the Software is furnished to do so, subject to
 * the following conditions:
 *
 * The above copyright notice and this permission notice shall be
 * included in all copies or substantial portions of the Software.
 *
 * THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND,
 * EXPRESS OR IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF
 * MERCHANTABILITY, FITNESS FOR A PARTICULAR PURPOSE AND
 * NONINFRINGEMENT. IN NO EVENT SHALL THE AUTHORS OR COPYRIGHT HOLDERS BE
 * LIABLE FOR ANY CLAIM, DAMAGES OR OTHER LIABILITY, WHETHER IN AN ACTION
 * OF CONTRACT, TORT OR OTHERWISE, ARISING FROM, OUT OF OR IN CONNECTION
 * WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN THE SOFTWARE.
 */
namespace Concrete5\Middleware;

class Concrete5BasicAuth extends \Slim\Middleware
{
    /**
     * @var string
     */
    protected $realm;

    /**
     * Constructor
     *
     * @param string $realm The HTTP Authentication realm
     */
    public function __construct($realm = 'Protected Area')
    {
        $this->realm = $realm;
    }
    
    /**
     * Is there a valid C5 instance around ?
     * @return bool
     */
    protected function isConcreteBooted() 
    {     
        if(defined('C5_EXECUTE') && C5_EXECUTE == true) {
            return true;   
        }
        
        return false;
    }
    
    /**
     * Completes the C5 login
     *
     * The logic is mainly the same code within the C5 login controller
     *
     * @param string $username
     * @param string $password
     * @return Exception|User
     */
    protected function doLogin($username, $password) 
    {
        
        /* Standard code from the C5 login controller and slightly modified */
        
        $ip = \Loader::helper('validation/ip');
		$vs = \Loader::helper('validation/strings');
        
        try {
			if (!$ip->check()) {				
				throw new \Exception($ip->getErrorMessage());
			}
			
			if ((!$vs->notempty($username)) || (!$vs->notempty($password))) {
				if (USER_REGISTRATION_WITH_EMAIL_ADDRESS) {
					throw new \Exception(t('An email address and password are required.'));
				} else {
					throw new \Exception(t('A username and password are required.'));
				}
			}
			
			$u = new \User($username, $password);
			if ($u->isError()) {
				switch($u->getError()) {
					case USER_NON_VALIDATED:
						throw new \Exception(t('This account has not yet been validated. Please check the email associated with this account and follow the link it contains.'));
						break;
					case USER_INVALID:
						if (USER_REGISTRATION_WITH_EMAIL_ADDRESS) {
							throw new \Exception(t('Invalid email address or password.'));
						} else {
							throw new \Exception(t('Invalid username or password.'));						
						}
						break;
					case USER_INACTIVE:
						throw new \Exception(t('This user is inactive. Please contact us regarding this account.'));
						break;
				}
			}
            
            return $u;
			
		} catch(\Exception $e) {
			$ip->logSignupRequest();
			if ($ip->signupRequestThreshholdReached()) {
				$ip->createIPBan();
			}
            return $e;
		}
        
    }
    

    /**
     * Call
     *
     * This method will check the HTTP request headers for previous authentication. If
     * the request has already authenticated, the next middleware is called. 
     */
    public function call()
    {   
        /* Get a handle on the response object */
        $res = $this->app->response();
        
        /* Check that Concrete5 is booted otherwise render an error */
        if(!$this->isConcreteBooted()) {
            $res->status(500);
            $res->write('The Concrete5 environment is not present, please boot it before constructing this class.', true);
        } else {
            
            /* Get the username & password submitted */
            $authUser = $this->app->request()->headers('PHP_AUTH_USER');
            $authPass = $this->app->request()->headers('PHP_AUTH_PW');
   
            /* Run the login */
            $r = $this->doLogin($authUser, $authPass);
            
            /* If we have a Concrete5 User object we've authenticated */
            if($r instanceof \User && $r->getUserID() > 0) {
                $this->app->concreteUser = $r;
                $this->next->call();
            } else {  
                /* If we receive an Exception back we render an unauthorized 
                 * screen with the login faliure specifics */
                $res->status(401);
                $res->header('WWW-Authenticate', sprintf('Basic realm="%s"', $this->realm));
                $res->write($r->getMessage(), true);      
            }
            
        }
    }
}
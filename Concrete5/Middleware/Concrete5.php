<?php
/**
 * Concrete5 Environment Bootstrap
 *
 * Use this middleware with your Slim Framework application to boot
 * the Concrete5 runtime.
 *
 * @author Oliver Green <green2go@gmail.com>
 * @version 1.0
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

class Concrete5 extends \Slim\Middleware
{
    public function call()
    {
        /* Get a handle on Slim */
        $app = $this->app;

        /* And one on the response object */
        $res = $app->response();
        
        /* Boot Concrete5, otherwise show an error */
        if(!$this->_bootConcrete()) {
            $res->status(500);
            $res->write('<b>500 Server Error<b/>: Failed to boot Concrete5.', true);
            return;  
        }
        
        /* Call the next piece of middleware */
        $this->next->call();
        
    }
    
    private function _bootConcrete() {
        
        /* Set the execute const */
        define('C5_EXECUTE', true);
        
        /* Set the base path to Concrete5 installation directory */
        define('DIR_BASE', '/path/to/concrete5');
        
        /* Skip page rendering */
        define('C5_ENVIRONMENT_ONLY', true);
        
        /* Prevents dispatcher from causing redirection to the base_url */
        define('REDIRECT_TO_BASE_URL', false);
        
        /* Include the C5 dispatcher - this is where the magic happens */
        @include(DIR_BASE . '/concrete/dispatcher.php');
        
        /* If we can call the C5 loader, we know C5 is there */
        if(!class_exists('Loader')) {
            return false;
        }
        
        return true;        
    }
}
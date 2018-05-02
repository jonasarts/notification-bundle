<?php

/*
 * This file is part of the jonasarts Notification bundle package.
 *
 * (c) Jonas Hauser <symfony@jonasarts.com>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace jonasarts\Bundle\NotificationBundle\Controller;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;

/**
 * MailDebugController
 * 
 * @Route("/_debug/mail")
 */
class MailDebugController extends Controller
{
    /**
     * Debug mails by opening /app_dev.php/_debug/mail/<entity>/<file.de.html>
     * 
     * @Route("/{entity}/{name}.{_locale}.{_format}", name="email_debug", defaults={"_locale"="de", "_format"="html"})
     */
    public function debugAction(Request $request, $entity, $name, $_locale, $_format)
    {

        $array = array(
            //'subject' => $name,
            
            'fullname' => 'John-Doe-1',
            'link' => 'http://www.domain.tld-2',
            'timestamp' => new \DateTime(),

            'subject' => 'Betreff-3',
            'subject_underline' => str_repeat('=', strlen('Betreff-3')),
            'message' => "Nachricht-4\nNachricht-5",
            
            );

        $template = 'NotificationBundle:'.$entity.':'.$name.'.'.$_locale.'.'.$_format.'.twig';

        $response = $this->render($template, $array);
        
        /*
        // always embed logo - this needs special processing
        $logo_src = '../../../../' . '....png';
        
        $response->setContent( str_replace('%logo_src%', $logo_src, $response->getContent()) );
        */

$head = <<<EOD
<head>
<meta http-equiv="X-UA-Compatible" content="IE=edge,chrome=1">
</head>
EOD;

        if ('txt' === $_format) { 
            $response->headers->set('Content-Type', 'text/html');
            $response->setContent( '<html><body><pre>' . $response->getContent() . '</pre></body><html>');
        } else {
            $response->headers->set('Content-Type', 'text/html');
            $response->setContent( '<html><body>'. $head . $response->getContent() .'</body></html>');
        }

        return $response;
    }
}

<?php

/*
 * This file is part of the FOSUserBundle package.
 *
 * (c) FriendsOfSymfony <http://friendsofsymfony.github.com/>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace FOS\UserBundle\Mailer;

use Swift_Mailer;
use Swift_Message;
use Twig_Environment;
use FOS\UserBundle\Model\UserInterface;
use FOS\UserBundle\Mailer\MailerInterface;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

/**
 * @author Christophe Coevoet <stof@notk.org>
 */
class TwigSwiftMailer implements MailerInterface
{
    protected $mailer;
    protected $router;
    protected $twig;
    protected $parameters;

    public function __construct(Swift_Mailer $mailer, UrlGeneratorInterface $router, Twig_Environment $twig, array $parameters)
    {
        $this->mailer = $mailer;
        $this->router = $router;
        $this->twig = $twig;
        $this->parameters = $parameters;
    }

    public function sendConfirmationEmailMessage(UserInterface $user)
    {
        $template = $this->getTemplate('confirmation');
        $url = $this->router->generate('fos_user_registration_confirm', array('token' => $user->getConfirmationToken()), true);

        $context = array(
            'user' => $user,
            'confirmationUrl' => $url
        );

        return $this->sendMessage($template, $context, $this->getFromEmail('confirmation'), $user->getEmail());
    }

    public function sendResettingEmailMessage(UserInterface $user)
    {
        $template = $this->getTemplate('resetting');
        $url = $this->router->generate('fos_user_resetting_reset', array('token' => $user->getConfirmationToken()), true);

        $context = array(
            'user' => $user,
            'confirmationUrl' => $url
        );

        return $this->sendMessage($template, $context, $this->getFromEmail('resetting'), $user->getEmail());
    }

    /**
     * @param string $templateName
     * @param array  $context
     * @param string $fromEmail
     * @param string $toEmail
     */
    public function sendMessage($templateName, $context, $fromEmail, $toEmail)
    {
        $message = $this->prepareMessage($templateName, $context, $fromEmail, $toEmail);

        return $this->getMailer()->send($message);
    }

    /**
     * @param string $templateName
     * @param array  $context
     * @param string $fromEmail
     * @param string $toEmail
     */
    protected function prepareMessage($templateName, $context, $fromEmail, $toEmail)
    {
        $twig = $this->getTwig();

        $context = $twig->mergeGlobals($context);
        $template = $twig->loadTemplate($templateName);
        $subject = $template->renderBlock('subject', $context);
        $textBody = $template->renderBlock('body_text', $context);
        $htmlBody = $template->renderBlock('body_html', $context);

        $message = Swift_Message::newInstance()
            ->setSubject($subject)
            ->setFrom($fromEmail)
            ->setTo($toEmail);

        if (!empty($htmlBody)) {
            $message->setBody($htmlBody, 'text/html')
                ->addPart($textBody, 'text/plain');
        } else {
            $message->setBody($textBody);
        }

        return $message;
    }

    protected function getTemplate($type)
    {
        return $this->parameters['template'][$type];
    }

    protected function getFromEmail($type)
    {
        return $this->parameters['from_email'][$type];
    }

    protected function getMailer()
    {
        return $this->mailer;
    }

    protected function getTwig()
    {
        return $this->twig;
    }
}

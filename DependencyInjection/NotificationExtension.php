<?php

/*
 * This file is part of the jonasarts Notification bundle package.
 *
 * (c) Jonas Hauser <symfony@jonasarts.com>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace jonasarts\Bundle\NotificationBundle\DependencyInjection;

use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Loader;
use Symfony\Component\HttpKernel\DependencyInjection\Extension;

/**
 * This is the class that loads and manages your bundle configuration
 *
 * To learn more see {@link http://symfony.com/doc/current/cookbook/bundles/extension.html}
 */
class NotificationExtension extends Extension
{
    /**
     * {@inheritDoc}
     */
    public function load(array $configs, ContainerBuilder $container)
    {
        $configuration = new Configuration();
        $config = $this->processConfiguration($configuration, $configs);

        // apply config
        $container->setParameter('notification.template', $config['template']);
        $container->setParameter('notification.template.loader', $config['template']['loader']);
        $container->setParameter('notification.template.path', $config['template']['path']);
        $container->setParameter('notification.from', array('address' => $config['from']['address'], 'name' => $config['from']['name']));
        if (!empty($config['sender']['address'] && !empty($config['sender']['name']))) {
            $container->setParameter('notification.sender', array('address' => $config['sender']['address'], 'name' => $config['sender']['name']));
        } else if (!empty($config['sender']['address'])) {
            $container->setParameter('notification.sender', array('address' => $config['sender']['address']));
        } else {
            $container->setParameter('notification.sender', null);
        }
        if (!empty($config['reply_to']['address']) && !empty($config['reply_to']['name'])) {
            $container->setParameter('notification.reply_to', array('address' => $config['reply_to']['address'], 'name' => $config['reply_to']['name']));
        } else if (!empty($config['reply_to']['address'])) {
            $container->setParameter('notification.reply_to', array('address' => $config['reply_to']['address']));
        } else {
            $container->setParameter('notification.reply_to', null);
        }
        $container->setParameter('notification.return_path', $config['return_path']);
        $container->setParameter('notification.subject_prefix', $config['subject_prefix']);

        $loader = new Loader\YamlFileLoader($container, new FileLocator(__DIR__.'/../Resources/config'));
        $loader->load('services.yml');

    }
}

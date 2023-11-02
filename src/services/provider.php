<?php
\defined('_JEXEC') or die;

use Joomla\CMS\Extension\PluginInterface;

use Joomla\CMS\Factory;
use Joomla\CMS\Plugin\PluginHelper;
#use Joomla\CMS\User\UserFactoryInterface;
#use Joomla\Database\DatabaseInterface;
use Joomla\DI\Container;
use Joomla\DI\ServiceProviderInterface;
use Joomla\Event\DispatcherInterface;
use GHSVS\Plugin\System\ImportfontsGhsvs\Extension\ImportfontsGhsvs;
use GHSVS\Plugin\System\ImportfontsGhsvs\Helper\ImportfontsGhsvsHelper;
use GHSVS\Plugin\System\ImportfontsGhsvs\Helper\Cssparser;

return new class () implements ServiceProviderInterface {
	public function register(Container $container): void
	{
		$container->set(
			PluginInterface::class,
			function (Container $container)
			{
				$dispatcher = $container->get(DispatcherInterface::class);
				$plugin = new ImportfontsGhsvs(
					$dispatcher,
					(array) PluginHelper::getPlugin('system', 'importfontsghsvs'),
					new ImportfontsGhsvsHelper(),
					new Cssparser(),
					#new FilterFieldHelper(),
				);
				$plugin->setApplication(Factory::getApplication());
				#$plugin->setDatabase($container->get(DatabaseInterface::class));
				#$plugin->setUserFactory($container->get(UserFactoryInterface::class));

				return $plugin;
			}
		);
	}
};

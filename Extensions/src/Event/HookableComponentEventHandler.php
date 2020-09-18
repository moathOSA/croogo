<?php
declare(strict_types=1);

namespace Croogo\Extensions\Event;

use Cake\Controller\Controller;
use Cake\Event\EventInterface;
use Cake\Event\EventListenerInterface;
use Cake\Utility\Hash;
use Croogo\Core\Controller\HookableComponentInterface;
use Croogo\Core\Croogo;
use Croogo\Extensions\Exception\ControllerNotHookableException;

/**
 * Class HookableComponentEventHandler
 */
class HookableComponentEventHandler implements EventListenerInterface
{

    /**
     * {@inheritDoc}
     */
    public function implementedEvents(): array
    {
        return [
            'Controller.beforeInitialize' => 'initialize'
        ];
    }

    /**
     * @param Event $event
     * @return void
     */
    public function initialize(EventInterface $event)
    {
        /* @var \Cake\Controller\Controller|\Croogo\Core\Controller\HookableComponentInterface $controller */
        $controller = $event->getSubject();

        if (!$controller instanceof HookableComponentInterface) {
            throw new ControllerNotHookableException([get_class($controller)]);
        }

        $components = $this->_getComponents($controller);
        foreach ($components as $component => $config) {
            $controller->_loadHookableComponent($component, $config);
        }
    }

    /**
     * Setup the components array
     *
     * @param void
     * @return void
     */
    protected function _setupComponents()
    {
        $components = [];

        $components = Hash::merge(
            $this->_defaultComponents,
            $this->_appComponents
        );

        foreach ($components as $component => $config) {
            if (!is_array($config)) {
                $component = $config;
                $config = [];
            }

            $this->loadComponent($component, $config);
        }
    }

    /**
     * @param $name
     * @param array $config
     *
     * @return mixed
     */
    public function loadComponent($name, array $config = [])
    {
        list(, $prop) = pluginSplit($name);
        list(, $modelProp) = pluginSplit($this->modelClass);
        $component = $this->components()->load($name, $config);
        if ($prop !== $modelProp) {
            $this->{$prop} = $component;
        }

        return $component;
    }

    /**
     * @param Controller $controller
     *
     * @return array
     */
    private function _getComponents(Controller $controller)
    {
        $properties = Croogo::options('Hook.controller_properties', $controller->getRequest());

        $components = [];
        foreach ($properties['_appComponents'] as $component => $config) {
            if (!is_array($config)) {
                $component = $config;
                $config = [];
            }

            $config = Hash::merge([
                'priority' => 10
            ], $config);

            $components[$component] = $config;
        }

        uasort($components, function ($previous, $next) {
            $previousPriority = $previous['priority'];
            $nextPriority = $next['priority'];

            if ($previousPriority === $nextPriority) {
                return 0;
            }

            return ($previousPriority < $nextPriority) ? -1 : 1;
        });

        return $components;
    }
}

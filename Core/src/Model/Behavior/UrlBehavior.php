<?php
declare(strict_types=1);

namespace Croogo\Core\Model\Behavior;

use Cake\Event\EventInterface;
use Cake\ORM\Behavior;
use Cake\ORM\Entity;
use Cake\ORM\Query;
use Croogo\Core\Link;

/**
 * Url Behavior
 *
 * @category Behavior
 * @package  Croogo.Croogo.Model.Behavior
 * @version  1.0
 * @author   Fahad Ibnay Heylaal <contact@fahad19.com>
 * @license  http://www.opensource.org/licenses/mit-license.php The MIT License
 * @link     http://www.croogo.org
 */
class UrlBehavior extends Behavior
{

    protected $_defaultConfig = [
        'url' => [],
        'fields' => [],
        'pass' => []
    ];

    public function beforeFind(EventInterface $event, Query $query, $options)
    {
        $query->formatResults(function ($results) {
            return $results->map(function ($row) {
                if (!$row instanceof Entity) {
                    return $row;
                }
                // Base URL
                $url = $this->getConfig('url');

                // Add named fields
                $fields = $this->getConfig('fields');
                if (is_array($fields)) {
                    foreach ($fields as $named => $field) {
                        if (is_numeric($named)) {
                            $named = $field;
                        }
                        if ($row->get($field)) {
                            $url[$named] = $row->get($field);
                        }
                    }
                }

                // Add passed fields
                $passed = $this->getConfig('pass');
                if (is_array($passed)) {
                    foreach ($passed as $field) {
                        if ($row->get($field)) {
                            $url[] = $row->get($field);
                        }
                    }
                }

                $row->set('url', new Link($url));
                $row->setDirty('url', false);

                return $row;
            });
        });
    }
}

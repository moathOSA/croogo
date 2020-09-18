<?php
declare(strict_types=1);

namespace Croogo\Example\Model\Behavior;

use Cake\Event\EventInterface;
use Cake\ORM\Behavior;
use Cake\ORM\Entity;
use Cake\ORM\Query;

/**
 * Example Behavior
 *
 * @category Behavior
 * @package  Croogo
 * @author   Fahad Ibnay Heylaal <contact@fahad19.com>
 * @license  http://www.opensource.org/licenses/mit-license.php The MIT License
 * @link     http://www.croogo.org
 */
class ExampleBehavior extends Behavior
{

    /**
     * afterFind callback
     *
     * @param Event $event
     * @param array $query
     * @return array
     */
    public function beforeFind(EventInterface $event, Query $query)
    {
        $query->formatResults(function ($results) {
            return $results->map(function ($result) {
                if ($result instanceof Entity) {
                    $result->body .= '<p>[Modified by ExampleBehavior]</p>';
                }

                return $result;
            });
        });
    }
}

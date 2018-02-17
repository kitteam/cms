<?php
/*
 * ...
 *
 * @package   This file is part of the Kit.cms
 * @author    Anton Popov <a.popov@kit.team>
 * @copyright Kit.team <http://www.kit.team>
 * @link      Kit.cms <http://www.kitcms.ru>
 */

namespace Classes\Model;

use Classes\Database\Model;

class Site extends Model
{
    protected $_table = 'Site';

    protected $fields = array(
        array(
            'title' => 'Сайт',
            'field' => 'host',
            'type' => 'varchar(255)',
            'key' => 'uni',
            'group' => 'main'
        ),
        array(
            'title' => 'Зеркала сайта',
            'field' => 'alias',
            'type' => 'varchar(255)',
            'null' => 'yes',
            'group' => 'main'
        ),
        array(
            'title' => 'Идентификатор макета дизайна',
            'field' => 'template',
            'type' => 'bigint(20) unsigned',
            'null' => 'yes',
            'key' => 'mul',
            'group' => 'template'
        ),
        array(
            'title' => 'Адрес административной части сайта',
            'field' => 'dashboard',
            'type' => 'varchar(255)',
            'default' => 'admin',
            'group' => 'service'
        ),
        array(
            'title' => 'Название сайта',
            'field' => 'title',
            'type' => 'varchar(255)',
            'group' => 'main'
        ),
        array(
            'title' => 'Описание',
            'field' => 'description',
            'type' => 'text',
            'null' => 'yes',
            'group' => 'main'
        ),
        array(
            'title' => 'Метаинформация',
            'field' => 'meta',
            'type' => 'longtext',
            'null' => 'yes',
            'group' => 'meta'
        )
    );

    public function section()
    {
        return $this->hasMany(__NAMESPACE__ . NS .'Section', 'site');
    }

    public function save()
    {
        $hosts = array($this->get('host'));
        if ($alias = $this->isDirty('alias')) {
            $hosts = array_merge($hosts, (array) $this->get('alias'));
        }
        $factory = self::whereHostIn($hosts);
        if (false === $this->isNew()) {
            $factory->whereNotIn('id', (array) $this->get('id'));
        }
        if ($factory->findOne()) {
            return false;
        }
        return parent::save();
    }

    public function delete()
    {
        // Массивы зависимых объектов
        $dependences = array();
        array_push($dependences, $this->section()->findMany());
        if (parent::delete()) {
            // Удаление зависимых объектов
            foreach ((array) $dependences as $dependence) {
                foreach ((array) $dependence as $depend) {
                    $depend->delete();
                }
            }
            return true;
        }
        return false;
    }

    public static function whereHostIn($hosts)
    {
        if (function_exists('idn_to_utf8')) {
            foreach ($hosts as $host) {
                $hosts[] = idn_to_utf8($host);
            }
        }
        $hosts = array_unique($hosts);
        $hosts = array_diff($hosts, array(''));
        $regex = '('. join('|', $hosts) .')';
        return self::whereAnyIs(
            array(
                array('Site.host' => '^'. $regex. '$'),
                array('Site.alias' => '[[:<:]]'. $regex .'[[:>:]]')
            ),
            array('Site.host' => 'REGEXP', 'Site.alias' => 'REGEXP')
        );
    }
}

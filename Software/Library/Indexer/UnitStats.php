<?php
/**
 * Created by PhpStorm.
 * User: Camiel
 * Date: 12-May-20
 * Time: 16:53
 */

namespace Grepodata\Library\Indexer;


class UnitStats
{
  const units = array(
    "sword"             => array('population' => 1,  'speed' => 8,   'uses_meteorology' => true, 'uses_cartography' => false, 'requires_transport' => true),
    "slinger"           => array('population' => 1,  'speed' => 14,  'uses_meteorology' => true, 'uses_cartography' => false, 'requires_transport' => true),
    "archer"            => array('population' => 1,  'speed' => 12,  'uses_meteorology' => true, 'uses_cartography' => false, 'requires_transport' => true),
    "hoplite"           => array('population' => 1,  'speed' => 6,   'uses_meteorology' => true, 'uses_cartography' => false, 'requires_transport' => true),
    "rider"             => array('population' => 3,  'speed' => 22,  'uses_meteorology' => true, 'uses_cartography' => false, 'requires_transport' => true),
    "chariot"           => array('population' => 4,  'speed' => 18,  'uses_meteorology' => true, 'uses_cartography' => false, 'requires_transport' => true),
    "catapult"          => array('population' => 15, 'speed' => 2,   'uses_meteorology' => true, 'uses_cartography' => false, 'requires_transport' => true),

    "siren"             => array('population' => 16, 'speed' => 16,  'uses_meteorology' => false, 'uses_cartography' => true, 'requires_transport' => false),
    "satyr"             => array('population' => 16, 'speed' => 136,  'uses_meteorology' => true, 'uses_cartography' => false, 'requires_transport' => true),
    "ladon"             => array('population' => 85, 'speed' => 100, 'uses_meteorology' => true, 'uses_cartography' => false, 'requires_transport' => true),
    "spartoi"           => array('population' => 10, 'speed' => 16,  'uses_meteorology' => true, 'uses_cartography' => false, 'requires_transport' => true),

    "manticore"         => array('population' => 45, 'speed' => 22,  'uses_meteorology' => true, 'uses_cartography' => false, 'requires_transport' => false),
    "sea_monster"       => array('population' => 50, 'speed' => 8,   'uses_meteorology' => false, 'uses_cartography' => true, 'requires_transport' => false),
    "harpy"             => array('population' => 14, 'speed' => 28,  'uses_meteorology' => true, 'uses_cartography' => false, 'requires_transport' => false),
    "pegasus"           => array('population' => 20, 'speed' => 35,  'uses_meteorology' => true, 'uses_cartography' => false, 'requires_transport' => false),
    "griffin"           => array('population' => 35, 'speed' => 18,  'uses_meteorology' => true, 'uses_cartography' => false, 'requires_transport' => false),
    "minotaur"          => array('population' => 30, 'speed' => 10,  'uses_meteorology' => true, 'uses_cartography' => false, 'requires_transport' => true),
    "zyklop"            => array('population' => 40, 'speed' => 8,   'uses_meteorology' => true, 'uses_cartography' => false, 'requires_transport' => true),
    "medusa"            => array('population' => 18, 'speed' => 6,   'uses_meteorology' => true, 'uses_cartography' => false, 'requires_transport' => true),
    "centaur"           => array('population' => 12, 'speed' => 18,  'uses_meteorology' => true, 'uses_cartography' => false, 'requires_transport' => true),
    "cerberus"          => array('population' => 30, 'speed' => 4,   'uses_meteorology' => true, 'uses_cartography' => false, 'requires_transport' => true),
    "fury"              => array('population' => 55, 'speed' => 20,  'uses_meteorology' => true, 'uses_cartography' => false, 'requires_transport' => true),
    "calydonian_boar"   => array('population' => 20, 'speed' => 16,  'uses_meteorology' => true, 'uses_cartography' => false, 'requires_transport' => true),
    "godsent"           => array('population' => 3,  'speed' => 16,  'uses_meteorology' => true, 'uses_cartography' => false, 'requires_transport' => true),

    "big_transporter"   => array('population' => 7,   'speed' => 8,   'uses_meteorology' => false, 'uses_cartography' => true, 'requires_transport' => false),
    "bireme"            => array('population' => 8,   'speed' => 15,  'uses_meteorology' => false, 'uses_cartography' => true, 'requires_transport' => false),
    "attack_ship"       => array('population' => 10,  'speed' => 13,  'uses_meteorology' => false, 'uses_cartography' => true, 'requires_transport' => false),
    "demolition_ship"   => array('population' => 8,   'speed' => 5,   'uses_meteorology' => false, 'uses_cartography' => true, 'requires_transport' => false),
    "small_transporter" => array('population' => 5,   'speed' => 15,  'uses_meteorology' => false, 'uses_cartography' => true, 'requires_transport' => false),
    "trireme"           => array('population' => 16,  'speed' => 15,  'uses_meteorology' => false, 'uses_cartography' => true, 'requires_transport' => false),
    "colonize_ship"     => array('population' => 170, 'speed' => 3,   'uses_meteorology' => false, 'uses_cartography' => true, 'requires_transport' => false),
  );
}

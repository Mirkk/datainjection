<?php
/*
 * @version $Id$
 LICENSE

 This file is part of the datainjection plugin.

 Datainjection plugin is free software; you can redistribute it and/or modify
 it under the terms of the GNU General Public License as published by
 the Free Software Foundation; either version 2 of the License, or
 (at your option) any later version.

 Datainjection plugin is distributed in the hope that it will be useful,
 but WITHOUT ANY WARRANTY; without even the implied warranty of
 MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 GNU General Public License for more details.

 You should have received a copy of the GNU General Public License
 along with datainjection. If not, see <http://www.gnu.org/licenses/>.
 --------------------------------------------------------------------------
 @package   datainjection
 @author    Wuerth Phoenix
 @copyright Copyright (c) 2010-2013 Datainjection plugin team
 @copyright Copyright (C) 2017-2022 Wuerth Phoenix, http://www.wuerth-phoenix.com
 @license   GPLv2+
            http://www.gnu.org/licenses/gpl.txt
 @link      https://forge.indepnet.net/projects/datainjection
 @link      http://www.glpi-project.org/
 @since     2009
 ---------------------------------------------------------------------- */

if (!defined('GLPI_ROOT')) {
   die("Sorry. You can't access directly to this file");
}

class PluginDatainjectionComputerVirtualMachineInjection extends ComputerVirtualMachine //MOMI from Item_Diskinjection !!
                                                implements PluginDatainjectionInjectionInterface {


   static function getTable($classname = null) { 

      $parenttype = get_parent_class();
      return $parenttype::getTable();
   }


   function isPrimaryType() {
      return true;
   }


   function connectedTo() {
      return array('Computer');
   }

   /**
    * @see plugins/datainjection/inc/PluginDatainjectionInjectionInterface::getOptions()
   **/
   function getOptions($primary_type='') {

      $tab           = Search::getOptions(get_parent_class($this));
	  
	  #these have to be added here because they are defined only in rawSearchOptionsToAdd!
	  #  -- put id as tab[id] and add linkfield.
	  $tab[161] = [
         'table'              => 'glpi_virtualmachinestates',
         'field'              => 'name',
         'name'               => __('State'),
         'forcegroupby'       => true,
         'massiveaction'      => false,
         'datatype'           => 'dropdown',
         'joinparams'         => [
            'beforejoin'         => [
               'table'              => self::getTable(),
               'joinparams'         => [
                  'jointype'           => 'child'
               ]
            ]
         ],
         'linkfield'		  => 'virtualmachinestates_id'
      ];
	  
	  $tab[162] = [
         'table'              => 'glpi_virtualmachinesystems',
         'field'              => 'name',
         'name'               => VirtualMachineSystem::getTypeName(1),
         'forcegroupby'       => true,
         'massiveaction'      => false,
         'datatype'           => 'dropdown',
         'joinparams'         => [
            'beforejoin'         => [
               'table'              => self::getTable(),
               'joinparams'         => [
                  'jointype'           => 'child'
               ]
            ]
         ],
         'linkfield'		  => 'virtualmachinesystems_id'
      ];

      $tab[163] = [
         'table'              => 'glpi_virtualmachinetypes',
         'field'              => 'name',
         'name'               => VirtualMachineType::getTypeName(1),
         'datatype'           => 'dropdown',
         'forcegroupby'       => true,
         'massiveaction'      => false,
         'joinparams'         => [
            'beforejoin'         => [
               'table'              => self::getTable(),
               'joinparams'         => [
                  'jointype'           => 'child'
               ]
            ]
         ],
         'linkfield'		  => 'virtualmachinetypes_id'
      ];

      //Remove some options because some fields cannot be imported
      $blacklist =  array();#  = PluginDatainjectionCommonInjectionLib::getBlacklistedOptions(get_parent_class($this));
      $notimportable = array();

      $options['ignore_fields'] = array_merge($blacklist, $notimportable);
	  $options['displaytype']   = ["dropdown"       => [161, 162, 163]];

      return PluginDatainjectionCommonInjectionLib::addToSearchOptions($tab, $options, $this);
   }


   /**
    * @param $values
    * @param $add                (true by default)
    * @param $rights    array
   **/
   
   function processAfterInsertOrUpdate($values, $add=true, $rights=array()) {
	  #error_log("FUNCTION EXECUTED in ".get_parent_class($this).":". print_r($values, true)); 

      if (isset($values['Computer']['id'])) {
		 $class   = get_parent_class($this);
         $item    = new $class();

		 $where   = [
    	    'name'     => $values[$class]['name'],
			'computers_id' => $values['Computer']['id'], #$values[$class]['computers_id'], 
         ];

		 $tmp = $values[$class]; 
		 
	     $tmp['computers_id'] 	= $values['Computer']['id'];    
	     $tmp['computers_id'] 	= $values['Computer']['id'];    
		 
         unset($tmp['id']);		
		 
         #$tmp['uuid'] = $values[$class]['uuid'];

         if (!countElementsInTable($item->getTable(), $where)) {
            $item->add($tmp);
         } else {
			$datas = getAllDataFromTable($item->getTable(), $where);
			
            foreach ($datas as $data) {
               //update only first item
               if (isset($tmp['id'])) {
                 continue;  
               }
               $tmp['id'] = $data['id'];
               $item->update($tmp);
            }
         }
      }
   }

   /**
    * @see plugins/datainjection/inc/PluginDatainjectionInjectionInterface::addOrUpdateObject()
   **/
   function addOrUpdateObject($values = [], $options = []) {
	  //error_log("THIS IS NEVER EXECUTED BECAUSE A COMPUTER IS ADDED, NOT A DISK!!"  );

      $lib = new PluginDatainjectionCommonInjectionLib($this, $values, $options);	    
      $lib->processAddOrUpdate(); 
      return $lib->getInjectionResults();
   }

}

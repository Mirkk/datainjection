<?php
/*
 ----------------------------------------------------------------------
 GLPI - Gestionnaire Libre de Parc Informatique
 Copyright (C) 2003-2008 by the INDEPNET Development Team.

 http://indepnet.net/   http://glpi-project.org/
 ----------------------------------------------------------------------

 LICENSE

   This file is part of GLPI.

    GLPI is free software; you can redistribute it and/or modify
    it under the terms of the GNU General Public License as published by
    the Free Software Foundation; either version 2 of the License, or
    (at your option) any later version.

    GLPI is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    GNU General Public License for more details.

    You should have received a copy of the GNU General Public License
    along with GLPI; if not, write to the Free Software
    Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA
 ------------------------------------------------------------------------
*/

// Original Author of file: Walid Nouh
// Purpose of file: Webservice methods
// ----------------------------------------------------------------------

class PluginDatainjectionWebservice {


   static function methodInject($params, $protocol) {

      if (isset ($params['help'])) {
         return array('uri'    => 'string,mandatory',
                      'base64' => 'string,optional',
                      'help'   => 'bool,optional');
      }

      if (!isset ($_SESSION['glpiID'])) {
         return PluginWebservicesMethodCommon::Error($protocol,
                                                     WEBSERVICES_ERROR_NOTAUTHENTICATED);
      }

      if (!isset($params['uri']) && !isset($params['base64'])) {
         return PluginWebservicesMethodCommon::Error($protocol,
                                                     WEBSERVICES_ERROR_MISSINGPARAMETER,
                                                     '', 'uri or base64');
      }

      if (!isset ($params['models_id'])) {
         return PluginWebservicesMethodCommon::Error($protocol,
                                                     WEBSERVICES_ERROR_MISSINGPARAMETER,
                                                     'models_id');
      } else {
         $model = new PluginDatainjectionModel;
         if (!$model->getFromDB($params['models_id'])) {
               return PluginWebservicesMethodCommon::Error($protocol,
                                                           WEBSERVICES_ERROR_NOTFOUND,
                                                           'Model unknown');
               
            } elseif (!$model->can($params['models_id'],'r')) {
               return PluginWebservicesMethodCommon::Error($protocol,
                                                           WEBSERVICES_ERROR_NOTALLOWED,
                                                           'You cannot access this model');
            }

      }

      //Check entity
      if (!isset ($params['entities_id'])) {
         return PluginWebservicesMethodCommon::Error($protocol,
                                                     WEBSERVICES_ERROR_MISSINGPARAMETER,
                                                     'entities_id');
      } else {
         $entities_id = $params['entities_id'];
         if ($entities_id > 0 ) {
            $entity = new Entity;
            if (!$entity->getFromDB($entities_id)) {
               return PluginWebservicesMethodCommon::Error($protocol,
                                                           WEBSERVICES_ERROR_NOTFOUND,
                                                           'Entity unknown');
               
            } elseif (!haveAccessToEntity($entities_id)) {
               return PluginWebservicesMethodCommon::Error($protocol,
                                                           WEBSERVICES_ERROR_NOTALLOWED,
                                                           'You cannot access this entity');
            }
         }
      }

      $model         = new PluginDatainjectionModel;
      $document_name = basename($params['uri']);
      $filename      = tempnam(PLUGIN_DATAINJECTION_UPLOAD_DIR, 'PWS');

      if (!$model->getFromDB($params['models_id'])) {
         return PluginWebservicesMethodCommon::Error($protocol,
                                                     WEBSERVICES_ERROR_NOTFOUND, 'models_id');
      }

      $response = PluginWebservicesMethodCommon::uploadDocument($params, $protocol, $filename,
                                                                $document_name);

      if (PluginWebservicesMethodCommon::isError($protocol, $response)) {
         return $response;
      }

      $options = array('file_encoding'     => PluginDatainjectionBackend::ENCODING_AUTO,
                       'webservice'        => true,
                       'original_filename' => $params['uri'],
                       'unique_filename'   => $filename,
                       'mode'              => PluginDatainjectionModel::PROCESS,
                       'delete_file'       => false,
                       'protocol'          => $protocol);

      $results = array();

      if ($response = $model->processUploadedFile($options)) {
         $engine  = new PluginDatainjectionEngine($model, array(), $params['entities_id']);
         foreach ($model->injectionData->getDatas() as $id => $data) {
            $results[] = $engine->injectLine($data[0], $id);
         }
         $model->cleanData();
         return $results;
      } else {
         return $response;
      }
   }


   static function methodGetModel($params,$protocol) {
      $params['itemtype'] = 'PluginDatainjectionModel';
      return PluginWebservicesMethodInventaire::methodGetObject($params, $protocol);
   }


  static function methodListModels($params, $protocol) {
      $params['itemtype'] = 'PluginDatainjectionModel';
      return PluginWebservicesMethodInventaire::methodListObjects($params, $protocol);
   }


   static function methodListItemtypes($params, $protocol) {

      if (isset ($params['help'])) {
         return array('help' => 'bool,optional');
      }

      if (!isset ($_SESSION['glpiID'])) {
         return self::Error($protocol, WEBSERVICES_ERROR_NOTAUTHENTICATED);
      }

      return PluginDatainjectionInjectionType::getItemtypes();
   }

}
?>
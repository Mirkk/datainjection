<?php
/*
 * @version $Id$
 LICENSE

 This file is part of the order plugin.

 Datainjection plugin is free software; you can redistribute it and/or modify
 it under the terms of the GNU General Public License as published by
 the Free Software Foundation; either version 2 of the License, or
 (at your option) any later version.

 Datainjection plugin is distributed in the hope that it will be useful,
 but WITHOUT ANY WARRANTY; without even the implied warranty of
 MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 GNU General Public License for more details.

 You should have received a copy of the GNU General Public License
 along with datainjection; along with Behaviors. If not, see <http://www.gnu.org/licenses/>.
 --------------------------------------------------------------------------
 @package   datainjection
 @author    the datainjection plugin team
 @copyright Copyright (c) 2010-2011 Order plugin team
 @license   GPLv2+
            http://www.gnu.org/licenses/gpl.txt
 @link      https://forge.indepnet.net/projects/datainjection
 @link      http://www.glpi-project.org/
 @since     2009
 ---------------------------------------------------------------------- */
 
define('GLPI_ROOT', '../../..');
include (GLPI_ROOT . "/inc/includes.php");

if (!isset ($_GET["id"])) {
   $_GET["id"] = "";
}

if (!isset ($_GET["withtemplate"])) {
   $_GET["withtemplate"] = "";
}

$model = new PluginDatainjectionModel();
$model->checkGlobal('r');

/* add */
if (isset ($_POST["add"])) {
   $model->check(-1,'w',$_POST);
   $newID = $model->add($_POST);

   //Set display to the advanced options tab
   setActiveTab('PluginDatainjectionModel', 2);
   glpi_header(getItemTypeFormURL('PluginDatainjectionModel')."?id=$newID");

/* delete */
} else if (isset ($_POST["delete"])) {
   $model->check($_POST['id'],'w');
   $model->delete($_POST);
   $model->redirectToList();

/* update */
} else if (isset ($_POST["update"])) {
   //Update model
   $model->check($_POST['id'], 'w');
   $model->update($_POST);

   $specific_model = PluginDatainjectionModel::getInstance('csv');
   $specific_model->saveFields($_POST);
   glpi_header($_SERVER['HTTP_REFERER']);

/* update order */
} else if (isset ($_POST["validate"])) {
   $model->check($_POST['id'],'w');
   $model->switchReadyToUse();
   glpi_header($_SERVER['HTTP_REFERER']);

} else if (isset($_POST['upload'])) {
   if (!empty($_FILES)) {
      $model->check($_POST['id'],'w');

      if ($model->processUploadedFile(array('file_encoding' => 'csv',
                                            'mode'          => PluginDatainjectionModel::CREATION))) {
         setActiveTab('PluginDatainjectionModel', 4);
      }else {
         addMessageAfterRedirect($LANG['datainjection']['fileStep'][4], true, ERROR, true);
      }
   }

   glpi_header($_SERVER['HTTP_REFERER']);

} else if (isset($_GET['sample'])) {
   $model->check($_GET['sample'], 'r');
   $modeltype = PluginDatainjectionModel::getInstance($model->getField('filetype'));
   $modeltype->getFromDBByModelID($model->getField('id'));
   $modeltype->showSample($model);
   exit (0);
}

commonHeader($LANG['datainjection']['profiles'][1], '', "plugins", "datainjection", "model");

$model->showForm($_GET["id"]);

commonFooter();

?>
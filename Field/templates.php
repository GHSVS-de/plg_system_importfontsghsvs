<?php
/**
 * @copyright Copyright (C) 2011 Simplify Your Web, Inc. All rights reserved.
 * @license  GNU General Public License version 3 or later; see LICENSE.txt
 */

defined('JPATH_PLATFORM') or die;

use Joomla\CMS\Application\ApplicationHelper;
use Joomla\CMS\Factory;
use Joomla\CMS\Form\FormHelper;
use Joomla\CMS\HTML\HTMLHelper;

FormHelper::loadFieldClass('list');

class plgSystemImportFontsGhsvsFormFieldTemplates extends JFormFieldList
{
 public $type = 'Templates';
  
 protected function getOptions()
 {
  $options = array();
  
  $client = ApplicationHelper::getClientInfo('site', true);
  
  $db = Factory::getDBO();
  $query = $db->getQuery(true);
  
  $query->select('s.id, s.title, e.name as name, s.template');
  $query->from('#__template_styles as s');
  $query->where('s.client_id = ' . (int) $client->id);
  $query->order('template');
  $query->order('title');
  $query->join('LEFT', '#__extensions as e on e.element=s.template');
  $query->where('e.enabled=1');
  $query->where($db->quoteName('e.type') . '=' . $db->quote('template'))
  ->group('template')
  ;
 
  $db->setQuery($query);
 
  if ($error = $db->getErrorMsg()) {
   throw new Exception($error);
  } 
  
  $templates = $db->loadObjectList();   
 
  foreach ($templates as $item) {
   $options[] = HTMLHelper::_('select.option', $item->template, $item->template);
  } 

  // Merge any additional options in the XML definition.
  $options = array_merge(parent::getOptions(), $options);

  return $options;
 }
}

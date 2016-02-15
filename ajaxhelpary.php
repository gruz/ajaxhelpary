<?php defined('_JEXEC') or die;

jimport('joomla.plugin.plugin');
class plgAjaxAjaxhelpary extends JPlugin
{

	function onAjaxAjaxHelpAry() {
		JSession::checkToken( 'get' ) or die( 'Invalid Token' );


		$app = JFactory::getApplication();
		if ( $app->isAdmin() ) {
			$scope = 'admin';
		} else { $scope = 'site'; }


		$jinput = JFactory::getApplication()->input;

		$plg_type = $jinput->get('plg_type',null);
		$plg_name = $jinput->get('plg_name',null);
		$function = $jinput->get('function',null);
		$uniq = $jinput->get('uniq',null);
		$serialize = $jinput->get('serialize',null);

		$limitplugins = $this->params->get('limitplugins',1);
		if ($limitplugins) {
			$params = $this->params->get('list_templates');
			$list_templates = json_decode($params);
			if (!empty($params) && $list_templates === NULL) { // The default joomla installation procedure doesn't store defaut params into the DB in the correct way
				$params = str_replace("'",'"',$params);
				$list_templates = json_decode($params);
			}

			$isOk = false;
			if (!empty($list_templates) && !empty($list_templates->plg_type)) {
				foreach($list_templates->plg_type as $k=>$v) {
					if (
						$plg_type == $list_templates->plg_type[$k] &&
						$plg_name == $list_templates->plg_name[$k] &&
						$function == $list_templates->function[$k]
					) {
						if ($list_templates->scope[$k] == 'both' || $list_templates->scope[$k] == $scope) {
							$isOk = true;
							break;
						}
					}
				}
			}
			if (!$isOk) {
				return JError::raiseWarning(403,JText::_('JERROR_ALERTNOAUTHOR'). '##error##');
			}
		}

		//$plugin = JPluginHelper::getPlugin($plg_type, $plg_name);

		JPluginHelper::importPlugin( $plg_type, $plg_name);
		$dispatcher = JEventDispatcher::getInstance();
		$results = $dispatcher->trigger( $function, array($uniq,$serialize) );

		if (!empty($results)) {
			return $results[0];
		}
	}
}

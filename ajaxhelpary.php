<?php
/**
 * Ajax helper plugin for Gruz extensions
 *
 * @package    AjaxHelpAry
 *
 * @author     Gruz <arygroup@gmail.com>
 *
 * @copyright  0000 Copyleft - All rights reversed
 *
 * @license    GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

// No direct access
defined('_JEXEC') or die;

jimport('joomla.plugin.plugin');

/**
 * Plugin class
 *
 * @author  Gruz <arygroup@gmail.com>
 * @since   0.0.1
 */
class PlgAjaxAjaxhelpary extends JPlugin
{
	/**
	 * Entrance point to ajax calls
	 *
	 * @return   void
	 */
	public function onAjaxAjaxHelpAry()
	{
		$jinput = JFactory::getApplication()->input;
		$scope = $jinput->get('scope', null);
		$serialize = $jinput->get('serialize', null);

		if ($scope == 'this')
		{
			$this->getFEURl($serialize);

			return;
		}
/*
$tmp = array('unsubscribe'=>'a@a.com');
$tmp = base64_encode(serialize($tmp));
echo '<pre> Line: '.__LINE__.' '.PHP_EOL;
print_r($tmp);
echo PHP_EOL.'</pre>'.PHP_EOL;
$tmp = unserialize(base64_decode($serialize));
echo '<pre> Line: '.__LINE__.' '.PHP_EOL;
print_r($tmp);
echo PHP_EOL.'</pre>'.PHP_EOL;
*/
		if ($scope != 'direct')
		{
			JSession::checkToken('get') or die('Invalid Token');
		}

		$app = JFactory::getApplication();

		if ($app->isAdmin())
		{
			$scope = 'admin';
		}
		else
		{
			$scope = 'site';
		}

		$plg_type = $jinput->get('plg_type', null);
		$plg_name = $jinput->get('plg_name', null);
		$function = $jinput->get('function', null);
		$uniq = $jinput->get('uniq', null);

		$limitplugins = $this->params->get('limitplugins', 1);

		if ($limitplugins)
		{
			$params = $this->params->get('list_templates');
			$list_templates = json_decode($params);

			if (!empty($params) && $list_templates === null)
			{
				// The default joomla installation procedure doesn't store defaut params into the DB in the correct way
				$params = str_replace("'", '"', $params);
				$list_templates = json_decode($params);
			}

			$isOk = false;

			if (!empty($list_templates) && !empty($list_templates->plg_type))
			{
				foreach ($list_templates->plg_type as $k => $v)
				{
					if ($plg_type == $list_templates->plg_type[$k]
						&& $plg_name == $list_templates->plg_name[$k]
						&& $function == $list_templates->function[$k])
					{
						if ($list_templates->scope[$k] == 'both' || $list_templates->scope[$k] == $scope)
						{
							$isOk = true;
							break;
						}
					}
				}
			}

			if (!$isOk)
			{
				die(JText::_('JERROR_ALERTNOAUTHOR') . '##error##');
			}
		}

		// $plugin = JPluginHelper::getPlugin($plg_type, $plg_name);

		JPluginHelper::importPlugin($plg_type, $plg_name);
		$dispatcher = JEventDispatcher::getInstance();
		$results = $dispatcher->trigger($function, array($uniq, $serialize));

		if (!empty($results))
		{
			return $results[0];
		}
	}

	/**
	 * The function to be called from AJAX to build really working SEF links from backend.
	 *
	 * Creates a backend user session, gets JRoute::_() and delets the session.
	 * It's a must to fake login-logout at FE, as JRoute::_() doesn't create correct links
	 * for e.g. content items limited to Registred if you are not logged in at FE.
	 *
	 * @param   string  $serialize  Serialized string
	 *
	 * @return   void
	 */
	public function getFEURl($serialize)
	{
		$app	= JFactory::getApplication();

		// Has to work as a FE called function
		if ($app->isAdmin())
		{
			return;
		}


		$jinput = JFactory::getApplication()->input;
		$userId = $jinput->get('userid', null);

		if (empty($userId))
		{
			return;
		}

		$url = unserialize(base64_decode($serialize));

		$user	= JFactory::getUser();

		if ($user->id == $userId)
		{
			echo JRoute::_($url);

			return;
		}

		/*
		if ($user->id) {
			JError::raiseWarning('SOME_ERROR_CODE', JText::_('SWITCHUSER_YOU_HAVE_LOGIN_LOGOUT_FIRST'));
			return $app->redirect('index.php');
		}
		*/

		/*
		$cookie = md5(JApplication::getHash('site'));

		// ~ $cookie = md5(JApplication::getHash('administrator'));


		$backendSessionId = $jinput->cookie->get($cookie, null);
		*
		$db		= JFactory::getDbo();

		$query = 'SELECT userid'
			. ' FROM #__session'
			. ' WHERE session_id = ' . $db->Quote($backendSessionId);

			// ~ . ' AND client_id = 1'
			// ~ . ' AND guest = 0'

		$db->setQuery($query);

		$user = $db->loadResut();
		*/

		$instance = JFactory::getUser($userId);

		if (empty($instance->id))
		{
			return;
		}

		/*
		// If _getUser returned an error, then pass it back.
		if (JError::isError($instance))
		{
			return;
		}
		*/

		// Mark the user as logged in
		$instance->set('guest', 0);
		$instance->set('aid', 1);

		// Register the needed session variables
		$session = JFactory::getSession();
		$session->set('user', $instance);

		// Get the session object
		$table = JTable::getInstance('session');
		$table->load($session->getId());
		$table->guest 		= $instance->get('guest');
		$table->username 	= $instance->get('username');
		$table->userid 		= intval($instance->get('id'));

		$table->update();
		echo JRoute::_($url);
		$table->delete();

		die();
	}
}

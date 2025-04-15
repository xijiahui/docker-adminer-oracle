<?php
namespace docker {
	function adminer_object() {
		require_once('plugins/plugin.php');

		class Adminer extends \AdminerPlugin {
			function _callParent($function, $args) {
				if ($function === 'loginForm') {
					ob_start();
					$return = \Adminer::loginForm();
					$form = ob_get_clean();

					$filePath = "/usr/local/oracle/instantclient_19_26/network/admin/tnsnames.ora";
					if (file_exists($filePath) && is_file($filePath)) {
						$pattern = '/<select name=\'auth\[driver\]\'>.*?<\/select>/s';
						$replacement = '<select name=\'auth[driver]\'><option value="oracle" selected> Oracle    (beta) </select>';
						$form = preg_replace($pattern, $replacement, $form);	

						$pattern = '/name="auth\[db\]"\s+value="[^"]*"/';
						$replacement = 'name="auth[db]" value="" readonly';
						$form = preg_replace($pattern, $replacement, $form);
					}

					if (!empty($_ENV['ORACLE_SID'])) {
						if (preg_match('/name="auth\[server\]"[^>]*title="hostname\[:port\]"/', $form, $matches)) {
							$form = str_replace($matches[0], 'name="auth[server]" value="'.($_ENV['ORACLE_SID']).'" readonly title="hostname[:port]"', $form);
						}
					}

					echo str_replace('name="auth[server]" value="" title="hostname[:port]"', 'name="auth[server]" value="'.($_ENV['ADMINER_DEFAULT_SERVER'] ?: 'db').'" title="hostname[:port]"', $form);

					return $return;
				}

				return parent::_callParent($function, $args);
			}
		}

		$plugins = [];
		foreach (glob('plugins-enabled/*.php') as $plugin) {
			$plugins[] = require($plugin);
		}

		return new Adminer($plugins);
	}
}

namespace {
	if (basename($_SERVER['DOCUMENT_URI'] ?? $_SERVER['REQUEST_URI']) === 'adminer.css' && is_readable('adminer.css')) {
		header('Content-Type: text/css');
		readfile('adminer.css');
		exit;
	}

	function adminer_object() {
		return \docker\adminer_object();
	}

	require('adminer.php');
}

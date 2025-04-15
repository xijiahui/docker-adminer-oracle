<?php
namespace docker {
	final class DefaultServerPlugin {
		public function __construct(
			private \AdminerPlugin $adminer
		) { }

		public function loginFormField(...$args) {
			return (function (...$args) {
				$field = \Adminer\Adminer::loginFormField(...$args);
	
				$filePath = "/usr/local/oracle/instantclient_19_26/network/admin/tnsnames.ora";
				if (file_exists($filePath) && is_file($filePath)) {
					$pattern = '/<select name=\'auth\[driver\]\'>.*?<\/select>/s';
					$replacement = '<select name=\'auth[driver]\'><option value="oracle" selected> Oracle    (beta) </select>';
					$field = preg_replace($pattern, $replacement, $field);	
					
					$pattern = '/name="auth\[db\]"\s+value="[^"]*"/';
					$replacement = 'name="auth[db]" value="" readonly';
					$field = preg_replace($pattern, $replacement, $field);
				}

				if (!empty($_ENV['ORACLE_SID'])) {
					if (preg_match('/name="auth\[server\]"[^>]*title="hostname\[:port\]"/', $field, $matches)) {
						$field = str_replace($matches[0], 'name="auth[server]" value="'.($_ENV['ORACLE_SID']).'" readonly title="hostname[:port]"', $field);
					}
				}
				
				return \str_replace(
					'name="auth[server]" value="" title="hostname[:port]"',
					\sprintf('name="auth[server]" value="%s" title="hostname[:port]"', ($_ENV['ADMINER_DEFAULT_SERVER'] ?: 'db')),
					$field,
				);
			})->call($this->adminer, ...$args);
		}
	}

	function adminer_object() {
		require_once('plugins/plugin.php');

		$plugins = [];
		foreach (glob('plugins-enabled/*.php') as $plugin) {
			$plugins[] = require($plugin);
		}

		// Load the DefaultServerPlugin last to give other plugins a chance to
		// override loginFormField() if they wish to.
		$plugins[] = &$loginFormPlugin;

		$adminer = new \AdminerPlugin($plugins);

		$loginFormPlugin = new DefaultServerPlugin($adminer);

		return $adminer;
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

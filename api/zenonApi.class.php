<?php
class zenonApi extends server {

    private $_ojsUser;
    private $_locale;

    function __construct($data, $logger, array $settings = array()) {
        parent::__construct($data, $logger, $settings);
    }

    function start() {
        $this->_loadOJS();
    }

    private function _loadOJS() {
        // where am I?
        preg_match('#(.+)\/plugins\/(.*)\/api#', dirname(__file__), $m);
        $ojs_path = $m[1];
        $plugin_path = $m[2];

        // load OJS
        if (!defined("OJS_PRESENT") or !OJS_PRESENT) {
            require_once($ojs_path . '/tools/bootstrap.inc.php');
        }

		// Initialize the request object with a page router
		$application = Application::getApplication();
		$request = $application->getRequest();
		import('classes.core.PageRouter');
		$router = new PageRouter();
		$router->setApplication($application);
		$request->setRouter($router);

		// Initialize the locale and load generic plugins.
		AppLocale::initialize($request);

		// return info
		$this->return["system"] = $application->getName() . " " . $application->getCurrentVersion()->getVersionString();
    }

    function mapping() {
		$dao = new DAO();
		$url = Config::getVar('general', 'base_url') . '/index.php/';
		$zid = (isset($this->data["/"]) and isset($this->data["/"][0]))
			? preg_replace('/\D/', '', $this->data["/"][0])
			: false;
		$sql = "select
				  replace(a_s.setting_value,'&dfm','') as zenonid,
				  concat('$url', j.path, '/catalog/book/', a.submission_id) as url
				from
				  published_submissions as p_a
					left join submissions as a on a.submission_id = p_a.submission_id
					left join submission_settings as a_s on p_a.submission_id = a_s.submission_id
					left join presses as j on j.press_id = a.context_id
				where
					setting_name in('pub-id::other::zenon','zenon_id')
				  and setting_value not in ('', '(((new)))')
				  and a.status = 3
				  and j.enabled = 1" .
			($zid ? " and a_s.setting_value = '$zid'" : '');
		$res = $dao->retrieve($sql);
		$this->return["publications"] = $res->getAssoc();
		$res->Close();
	}

    function finish() {

    }
}


?>

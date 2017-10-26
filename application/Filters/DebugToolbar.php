<?php namespace App\Filters;

use CodeIgniter\Filters\FilterInterface;
use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;
use Config\App;
use Config\Services;

class DebugToolbar implements FilterInterface
{
	/**
	 *
	 *
	 * @param RequestInterface|\CodeIgniter\HTTP\IncomingRequest $request
	 *
	 * @return mixed
	 */
	public function before(RequestInterface $request)
	{
		if ($request->getGet('debugbar') !== null)
        {
        	ob_start();
	        include(BASEPATH . 'Debug/Toolbar/toolbarloader.js.php');
	        $output = ob_get_contents();
	        @ob_end_clean();
		    exit($output);
		}

		if ($request->getGet('debugbar_time'))
		{
			helper('security');

			$file = sanitize_filename('debugbar_' . $request->getGet('debugbar_time'));
		    $filename = WRITEPATH . sanitize_filename('debugbar_' . $request->getGet('debugbar_time'));

		    if (file_exists($filename))
		    {
		    	$contents = file_get_contents($filename);
		    	unlink($filename);
			    exit($contents);
		    }

			// File was not written or do not exists
		    exit('<script id="toolbar_js">console.log(\'CI DebugBar: File "WRITEPATH/' . $file . '" not found.\')</script>');
		}
	}

	//--------------------------------------------------------------------

	/**
	 * If the debug flag is set (CI_DEBUG) then collect performance
	 * and debug information and display it in a toolbar.
	 *
	 * @param RequestInterface|\CodeIgniter\HTTP\IncomingRequest $request
	 * @param ResponseInterface|\CodeIgniter\HTTP\Response $response
	 *
	 * @return mixed
	 */
	public function after(RequestInterface $request, ResponseInterface $response)
	{
		$format = $response->getHeaderLine('content-type');

		if ( ! is_cli() && CI_DEBUG && strpos($format, 'html') !== false)
		{
			global $app;

			$toolbar = Services::toolbar(new App());
			$stats   = $app->getPerformanceStats();
			$output  = $toolbar->run(
				$stats['startTime'],
				$stats['totalTime'],
				$stats['startMemory'],
				$request,
				$response
			);

			helper(['filesystem', 'url']);

			// Updated to time() to can get history
			$time = time();

			write_file(WRITEPATH . 'debugbar_' . $time, $output, 'w+');

			$script = PHP_EOL
				. '<script type="text/javascript" id="debugbar_loader" '
				. 'data-time="' . $time . '" '
				. 'src="' . site_url() . '?debugbar"></script>'
				. PHP_EOL;

			if (strpos($response->getBody(), '</body>') !== false)
			{
				return $response->setBody(str_replace('</body>', $script . '</body>',
					$response->getBody()));
			}

			return $response->appendBody($script);
		}
	}

	//--------------------------------------------------------------------
}

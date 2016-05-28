<?php namespace IGonics\PayPalIpnLaravel;

use Illuminate\Support\ServiceProvider;
use IGonics\PayPalIpnLaravel\PayPalIpn;

class PayPalIpnServiceProvider extends ServiceProvider {

	/**
	 * Indicates if loading of the provider is deferred.
	 *
	 * @var bool
	 */
	protected $defer = true;

	/**
	 * Bootstrap the application events.
	 *
	 * @return void
	 */
	public function boot()
	{

	}

	/**
	 * Register the service provider.
	 *
	 * @return void
	 */
	public function register()
	{
		$paypalIPN = new PayPalIpn();

        $this->app->singleton('PayPalIPN', function ($app) use ($paypalIPN) {
            return $paypalIPN;
        });
        $this->app->singleton('paypalipn', function ($app) use ($paypalIPN) {
            return $paypalIPN;
        });
	}

	/**
	 * Get the services provided by the provider.
	 *
	 * @return array
	 */
	public function provides()
	{
		return ['paypalipn','PayPalIPN'];
	}

}
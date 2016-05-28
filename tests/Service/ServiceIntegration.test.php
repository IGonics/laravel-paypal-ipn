<?php

use IGonics\PayPalIpnLaravel\Exception\InvalidIpnException;
use IGonics\PayPalIpnLaravel\Exception\IpnVerificationException;

class ServiceIntegrationTestCase extends Orchestra\Testbench\TestCase
{

    /**
     * Setup the test environment.
     */
    public function setUp()
    {
        parent::setUp();
        IPN::setEnvironment('sandbox');
    
        // set up code here
    }
    //
    public function testInvalidMessage()
    {
    	$this->expectException(InvalidIpnException::class);
    	$order = IPN::getOrder();
    }

    public function testInvalidVerification()
    {
    	$this->expectException(IpnVerificationException::class);
    	$order = IPN::getOrder(['foo' => 'bar']);
    }

    public function testSuccessfulVerification()
    {
    	$order = IPN::getOrder(['foo' => 'bar']);
    	$this->assetTrue(true);
    }

    protected function getPackageProviders($app)
    {
        return ['IGonics\PayPalIpnLaravel\PaypalIpnServiceProvider'];
    }

    protected function getPackageAliases($app)
    {
        return [
            'IPN' => 'IGonics\PayPalIpnLaravel\Facades\IPN'
        ];
    }

}
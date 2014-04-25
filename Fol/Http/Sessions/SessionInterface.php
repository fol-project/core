<?php
/**
 * Interface used by all sessions
 */
namespace Fol\Http\Sessions;

use Fol\Http\Request;
use Fol\Http\Response;

interface SessionInterface
{
	/**
	 * Sets the request used to manage this session
	 * 
	 * @param Request $request
	 * 
	 * @return void
	 */
	public function setRequest(Request $request);


	/**
	 * Prepare the session before send the response
	 * 
	 * @param Response $response
	 * 
	 * @return void
	 */
	public function prepare(Response $response);
}
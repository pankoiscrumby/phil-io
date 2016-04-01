<?php

/**
 * SlackController.php - created Mar 6, 2016 3:03:18 PM
 *
 * @copyright Copyright (c) pinkbigmacmedia
 *
 */
namespace Chuck\App\Api\Controller\Jokes;

/**
 *
 * SlackController
 *
 * @package Chuck\App\Api
 *
 */
class SlackController
{
    use \Chuck\Util\LoggerTrait;

    /**
     *
     * @param  string $text
     * @param  \Symfony\Component\HttpFoundation\Request $request
     * @return void
     */
    protected function doLogging(
        $text,
        \Symfony\Component\HttpFoundation\Request $request
    ) {
        $this->logInfo(
            json_encode([
                'type'      => 'slack_command',
                'reference' => $request->headers->get('HTTP_X_REQUEST_ID', \Chuck\Util::createSlugUuid()),
                'meta'      => [
                    'request'  => [
                        'token'        => $request->get('token'),
                        'team_id'      => $request->get('team_id'),
                        'team_domain'  => $request->get('team_domain'),
                        'channel_id'   => $request->get('channel_id'),
                        'channel_name' => $request->get('channel_name'),
                        'user_id'      => $request->get('user_id'),
                        'user_name'    => $request->get('user_name'),
                        'command'      => $request->get('command'),
                        'text'         => $request->get('text'),
                        'response_url' => $request->get('response_url')
                    ],
                    'response' => [
                        'text' => $text
                    ]
                ]
            ])
        );
    }

    /**
     *
     * @param \Chuck\JokeFacade $jokeFacade
     * @return string
     */
    protected function getCategoriesText(\Chuck\JokeFacade $jokeFacade)
    {
        $categoryNames = array_column($jokeFacade->getCategories(), 'name');
        asort($categoryNames);

        return sprintf(
            'Available categories are: `%s`.',
            implode('`, `', $categoryNames)
        );
    }

    /**
     *
     * @param  \Silex\Application                        $app
     * @param  \Symfony\Component\HttpFoundation\Request $request
     * @return \Symfony\Component\HttpFoundation\JsonResponse
     */
    public function indexAction(
        \Silex\Application $app,
        \Symfony\Component\HttpFoundation\Request $request
    ) {
        $text = null;
        $this->setLogger($app['monolog']);

        if (! empty($userText = $request->get('text'))) {

            if ('-cat' === $userText) {
                $text = $this->getCategoriesText($app['chuck.joke']);
            } else {
                $joke = $app['chuck.joke']->random($userText);
                $text = null != $joke->getValue()
                    ? $joke->getValue()
                    : sprintf(
                        'Sorry dude, no jokes found for the given category ("%s"). Type `-cat` to see available ones.',
                        $userText
                      );
            }

        } else {
            $joke = $app['chuck.joke']->random();
            $text = $joke->getValue();
        }

        $this->doLogging($text, $request);

        return new \Symfony\Component\HttpFoundation\JsonResponse(
            [
                'icon_url'      => 'https://api.chucknorris.io/img/avatar/chuck-norris.png',
                'response_type' => 'in_channel',
                'text'          => $text,
                'mrkdwn'        => true
            ],
            200,
            [
                'Access-Control-Allow-Origin'      => '*',
                'Access-Control-Allow-Credentials' => 'true',
                'Access-Control-Allow-Methods'     => 'GET, HEAD',
                'Access-Control-Allow-Headers'     => 'Content-Type, Accept, X-Requested-With'
            ]
        );
    }
}
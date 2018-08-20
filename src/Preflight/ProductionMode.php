<?php
declare(strict_types=1);

namespace Itineris\SagePay\Preflight;

use GFAPI;
use Itineris\Preflight\Checkers\AbstractChecker;
use Itineris\Preflight\Config;
use Itineris\Preflight\ResultFactory;
use Itineris\Preflight\ResultInterface;
use Itineris\SagePay\AddOn;
use Itineris\SagePay\Feed;

class ProductionMode extends AbstractChecker
{
    public const ID = 'gf-sagepay-production-mode';
    public const DESCRIPTION = 'Ensure all gf-sagepay feeds are in production mode.';

    /**
     * Returns the URL to the checker document web page.
     *
     * @return string
     */
    public function getLink(): string
    {
        return 'Use the source, Luke. Let go.';
    }

    /**
     * Run the check and return a result.
     *
     * @param Config $config Ignored.
     *
     * @return ResultInterface
     */
    public function check(Config $config): ResultInterface
    {
        $addOn = AddOn::get_instance();

        $rawFeeds = GFAPI::get_feeds(null, null, $addOn->get_slug());

        if (is_wp_error($rawFeeds)) {
            return ResultFactory::makeError($this, 'Unable to fetch feeds');
        }

        $feeds = array_map(function (array $rawFeed): Feed {
            return new Feed($rawFeed);
        }, $rawFeeds);

        $testFeeds = array_filter($feeds, function (Feed $feed): bool {
            return $feed->isTest();
        });

        $messages = array_map(function (Feed $feed): string {
            return sprintf(
                'Form ID: %1$d - Feed ID: %2$d',
                $feed->getFormId(),
                $feed->getId()
            );
        }, $testFeeds);

        return empty($messages)
            ? ResultFactory::makeSuccess($this)
            : ResultFactory::makeFailure(
                $this,
                array_merge(['Test feeds found:'], $messages)
            );
    }

    /**
     * Run the check and return a result.
     *
     * Assume the checker is enabled and its config make sense.
     *
     * @param Config $config The config instance.
     *
     * @return ResultInterface
     */
    protected function run(Config $config): ResultInterface
    {
        return ResultFactory::makeError($this, 'This line should not be ran.');
    }
}

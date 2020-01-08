<?php declare(strict_types=1);

namespace SwagVueStorefront\Test\Integration;

use PHPUnit\Framework\TestCase;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepositoryInterface;
use Shopware\Core\Framework\Test\TestCaseBase\IntegrationTestBehaviour;
use Shopware\Core\Framework\Test\TestCaseBase\SalesChannelApiTestBehaviour;
use Shopware\Core\Framework\Uuid\Uuid;
use Shopware\Core\PlatformRequest;
use SwagVueStorefront\SwagVueStorefront;

class NavigationControllerTest extends TestCase
{
    use IntegrationTestBehaviour;
    use SalesChannelApiTestBehaviour;

    const ENDPOINT_NAVIGATION = '/sales-channel-api/v'.PlatformRequest::API_VERSION.SwagVueStorefront::ENDPOINT_PATH.'/navigation';

    /**
     * @var string
     */
    private $salesChannelId;

    /**
     * @var string
     */
    private $rootId;

    /**
     * @var string
     */
    private $category1Id;

    /**
     * @var string
     */
    private $category2Id;

    /**
     * @var string
     */
    private $category1_1Id;

    /**
     * @var string
     */
    private $category2_1Id;

    /**
     * @var EntityRepositoryInterface
     */
    private $categoryRepository;

    public function setUp(): void
    {
        parent::setUp();

        $this->browser = $this->getSalesChannelBrowser();
        $this->salesChannelId = $this->getSalesChannelApiSalesChannelId();

        $this->categoryRepository = $this->getContainer()->get('category.repository');

        $this->rootId = Uuid::randomHex();
        $this->category1Id = Uuid::randomHex();
        $this->category2Id = Uuid::randomHex();
        $this->category1_1Id = Uuid::randomHex();
        $this->category2_1Id = Uuid::randomHex();
    }

    public function testResolveOneLevel(): void
    {
        $this->createCategories();

        $content = [
            'rootNode' => $this->rootId,
            'depth' => 1
        ];

        $this->salesChannelApiBrowser->request(
            'POST',
            self::ENDPOINT_NAVIGATION,
            $content
        );

        $result = \GuzzleHttp\json_decode($this->salesChannelApiBrowser->getResponse()->getContent());

        static::assertObjectHasAttribute('route', $result);
        static::assertObjectHasAttribute('resourceType', $result->route);

        static::assertObjectHasAttribute('count', $result);
        static::assertEquals(2, $result->count);

        static::assertObjectHasAttribute('children', $result);
        static::assertNull($result->children[0]->children);
    }

    public function testResolveFull(): void
    {
        $this->createCategories();

        $content = [
            'depth' => -1,
            'rootNode' => $this->rootId
        ];

        $this->salesChannelApiBrowser->request(
            'POST',
            self::ENDPOINT_NAVIGATION,
            $content
        );

        $result = \GuzzleHttp\json_decode($this->salesChannelApiBrowser->getResponse()->getContent());

        static::assertCount(2, $result->children);
        static::assertCount(1, $result->children[0]->children);
    }

    private function createCategories():void
    {
        $this->categoryRepository->upsert([
            [
                'id' => $this->rootId,
                'name' => 'root',
                'children' => [
                    [
                        'id' => $this->category1Id,
                        'name' => 'Category 1',
                        'children' => [
                            [
                                'id' => $this->category1_1Id,
                                'name' => 'Category 1.1',
                            ],
                        ],
                    ],
                    [
                        'id' => $this->category2Id,
                        'name' => 'Category 2',
                        'children' => [
                            [
                                'id' => $this->category2_1Id,
                                'name' => 'Category 2.1',
                            ],
                        ],
                    ],
                ],
            ],
        ], Context::createDefaultContext());
    }

}
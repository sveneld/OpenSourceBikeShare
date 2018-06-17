<?php
namespace Tests\Feature\Api;

use BikeShare\Domain\Bike\Bike;
use BikeShare\Http\Services\AppConfig;

class RentTest extends BaseApiTest
{
    /**
     * @var AppConfig
     */
    protected $appConfig;

    protected function setUp()
    {
        parent::setUp();
        $this->appConfig = app(AppConfig::class);
    }

    /** @test */
    public function renting_and_returning_ok()
    {
        $user = userWithResources();
        [$stand, $bike] = standWithBike();

        $response = $this->apiAs($user,'POST', '/api/rents', [
            'bike' => $bike->uuid
        ]);
        $response->assertStatus(200);
        $rent = $response->json();

        $bike = Bike::find($bike->id);
        $this->assertEquals('occupied', $bike->status);
        $this->assertEquals($user->id, $bike->user_id);
        $this->assertNull($bike->stand_id);

        $response = $this->apiAs($user,'POST', '/api/rents/' . $rent['uuid'] . '/close' , [
            'stand' => $stand->uuid
        ]);
        $response->assertStatus(200);

        $bike = $bike->fresh();
        $this->assertEquals('free', $bike->status);
        $this->assertNull($bike->user_id);
        $this->assertEquals($stand->id, $bike->stand_id);
    }
}

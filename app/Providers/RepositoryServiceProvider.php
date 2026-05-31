<?php

namespace App\Providers;

use App\Domain\Billing\Contracts\BookingPaymentGateway;
use App\Domain\Booking\Repositories\BookingRepositoryInterface;
use App\Domain\Room\Repositories\RoomRepositoryInterface;
use App\Infrastructure\Repositories\BookingRepository;
use App\Infrastructure\Repositories\RoomRepository;
use App\Support\File\Contracts\UploadFileInterface;
use App\Support\File\UploadFileHelper;
use App\Support\Payments\SslCommerz\SslCommerzBookingPaymentGateway;
use Illuminate\Support\ServiceProvider;

class RepositoryServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        $this->app->bind(BookingRepositoryInterface::class, BookingRepository::class);
        $this->app->bind(RoomRepositoryInterface::class, RoomRepository::class);
        $this->app->bind(UploadFileInterface::class, UploadFileHelper::class);
        $this->app->bind(BookingPaymentGateway::class, SslCommerzBookingPaymentGateway::class);
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        //
    }
}

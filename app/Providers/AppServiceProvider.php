<?php

namespace App\Providers;

use App\Models\Amenity;
use App\Models\Booking;
use App\Models\BookingCancellationRequest;
use App\Models\BookingGuest;
use App\Models\BookingGuestRoomHistory;
use App\Models\BookingPayment;
use App\Models\BookingPromotion;
use App\Models\BookingPromotionRoomUpgrade;
use App\Models\BookingPromotionServiceOffer;
use App\Models\BookingRoom;
use App\Models\BookingRoomChange;
use App\Models\BookingServiceItem;
use App\Models\BookingStaffAssignment;
use App\Models\ChatAssignmentLog;
use App\Models\ChatConversation;
use App\Models\ChatMessage;
use App\Models\Customer;
use App\Models\CustomerRequest;
use App\Models\CustomerRequestAttachment;
use App\Models\HotelReview;
use App\Models\Promotion;
use App\Models\PromotionRoomUpgradeOffer;
use App\Models\PromotionServiceOffer;
use App\Models\Room;
use App\Models\RoomCategory;
use App\Models\RoomCategoryImage;
use App\Models\RoomInspection;
use App\Models\RoomIssueAttachment;
use App\Models\RoomIssueRequest;
use App\Models\RoomIssueRoomHold;
use App\Models\Service;
use App\Models\Staff;
use App\Models\StaffFloorAssignment;
use App\Models\StaffRoomAssignment;
use App\Models\User;
use App\Observers\AppRealtimeObserver;
use App\Observers\BookingObserver;
use App\Observers\ChatMessageObserver;
use App\Observers\RoomInspectionObserver;
use App\Observers\RoomObserver;
use Illuminate\Support\ServiceProvider;
use Illuminate\Pagination\Paginator;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        Paginator::useBootstrapFive();

        Booking::observe(BookingObserver::class);
        Room::observe(RoomObserver::class);
        RoomInspection::observe(RoomInspectionObserver::class);
        ChatMessage::observe(ChatMessageObserver::class);

        foreach ([
            Amenity::class,
            BookingCancellationRequest::class,
            BookingGuest::class,
            BookingGuestRoomHistory::class,
            BookingPayment::class,
            BookingPromotion::class,
            BookingPromotionRoomUpgrade::class,
            BookingPromotionServiceOffer::class,
            BookingRoom::class,
            BookingRoomChange::class,
            BookingServiceItem::class,
            BookingStaffAssignment::class,
            ChatAssignmentLog::class,
            ChatConversation::class,
            Customer::class,
            CustomerRequest::class,
            CustomerRequestAttachment::class,
            HotelReview::class,
            Promotion::class,
            PromotionRoomUpgradeOffer::class,
            PromotionServiceOffer::class,
            RoomCategory::class,
            RoomCategoryImage::class,
            RoomIssueAttachment::class,
            RoomIssueRequest::class,
            RoomIssueRoomHold::class,
            Service::class,
            Staff::class,
            StaffFloorAssignment::class,
            StaffRoomAssignment::class,
            User::class,
        ] as $model) {
            $model::observe(AppRealtimeObserver::class);
        }
    }
}

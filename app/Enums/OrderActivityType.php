<?php

declare(strict_types=1);

namespace App\Enums;

enum OrderActivityType: string
{
    case DeliveryUpdated = 'delivery_updated';
    case EmailResent = 'email_resent';
    case FulfillmentCanceled = 'fulfillment_canceled';
    case FulfillmentStatusChanged = 'fulfillment_status_changed';
    case ItemsFulfilled = 'items_fulfilled';
    case NoteAdded = 'note_added';
    case OrderCanceled = 'order_canceled';
    case OrderEdited = 'order_edited';
    case OrderPlaced = 'order_placed';
    case PaymentReceived = 'payment_received';
    case PaymentRequestSent = 'payment_request_sent';
    case PaymentStatusChanged = 'payment_status_changed';
    case PaymentVoided = 'payment_voided';
    case RefundCompleted = 'refund_completed';
    case RefundFailed = 'refund_failed';
    case RefundPending = 'refund_pending';
    case ReturnApproved = 'return_approved';
    case ReturnCanceled = 'return_canceled';
    case ReturnCompleted = 'return_completed';
    case ReturnDeclined = 'return_declined';
    case ReturnLabelCreated = 'return_label_created';
    case ReturnReceived = 'return_received';
    case ReturnRequested = 'return_requested';
    case ShipmentVoidFailed = 'shipment_void_failed';
    case TrackingUpdated = 'tracking_updated';
}

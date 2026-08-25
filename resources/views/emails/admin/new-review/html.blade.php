@extends('emails.layout', ['title' => __('New review for :product', ['product' => $productTitle])])

@section('preheader'){{ __('A customer has left a new review.') }}@endsection

@section('content')
    <h1>{{ __('New product review') }}</h1>
    <p>{{ __('A new review has been submitted for :product.', ['product' => $productTitle]) }}</p>

    <table role="presentation" cellpadding="0" cellspacing="0" border="0" width="100%" style="margin: 16px 0;">
        <tr>
            <td style="background-color:#f9fafb; border-radius:8px; padding:16px 20px;">
                <table role="presentation" cellpadding="0" cellspacing="0" border="0" width="100%">
                    <tr>
                        <td style="font-size:12px; color:#6b7280;">{{ __('Product') }}</td>
                        <td align="{{ $isRtl ? 'left' : 'right' }}" style="font-size:13px; color:#0f172a; font-weight:600;">{{ $productTitle }}</td>
                    </tr>
                    <tr>
                        <td style="font-size:12px; color:#6b7280; padding-top:8px;">{{ __('Rating') }}</td>
                        <td align="{{ $isRtl ? 'left' : 'right' }}" style="font-size:14px; color:#b45309; letter-spacing:2px; padding-top:8px;">
                            {{ str_repeat('★', (int) $review->rating) }}{{ str_repeat('☆', max(0, 5 - (int) $review->rating)) }}
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>

    @if (! empty($review->content))
        <table role="presentation" cellpadding="0" cellspacing="0" border="0" width="100%" style="margin: 16px 0;">
            <tr>
                <td style="background-color:#f9fafb; border-radius:8px; padding:16px 20px;">
                    <div style="font-size:13px; color:#1f2937; white-space:pre-wrap; line-height:1.6;">{{ $review->content }}</div>
                </td>
            </tr>
        </table>
    @endif

    @include('emails.components.button', ['url' => $viewUrl, 'label' => __('Moderate reviews')])
@endsection

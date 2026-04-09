@extends('layouts.master')
@section('title')
    @lang('translation.tasks')
@endsection
@section('css')
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Playfair+Display:ital,wght@0,400..900;1,400..900&family=Readex+Pro:wght@160..700&display=swap" rel="stylesheet">
@endsection
@section('content')
    @component('components.breadcrumb')
        @slot('li_1')
            @lang('translation.appname')
        @endslot
        @slot('title')
            @lang('translation.tasks')
        @endslot
    @endcomponent
    <style type="text/css">
        .headTitle {
            font-family: "Playfair Display", sans-serif !important;
            font-weight: 600;
            font-size: 25px;
            color: #fff;
            width: 415px;
            padding: 10px;
            border-radius: 10px;
            padding-left: 20px;
            padding-bottom: 15px;
        }
        .textContent h5 {
            font-family: "Readex Pro", sans-serif !important;
            font-weight: 500;
            font-size: 14px;
            margin-top: 1rem;
        }
        .noMT {
            margin-top: unset !important;
        }
        .textContent p {
            font-family: "Readex Pro", sans-serif !important;
            font-weight: 200;
            font-size: 14px;
        }
        .bacGray {
            background: #f2f2f2;
            border-radius: 15px;
            margin-right: 10px;
            margin-left: 10px;
        }
        .sameBlock {
            display: inline-block;
        }
        .leftLine {
            position: absolute;
            left: 10px;
            margin-top: 46px;
        }
    </style>
    <div class="card">
        <div class="card-header">
            {{ trans('translation.show') }} {{ trans('translation.task.title_singular') }}
        </div>

        <div class="card-body">
            <div class="container-fluid">
                <div class="row justify-content-center pull-up px-5" id="print_area">
                    <div class="col-md-12">
                        <div class="card">
                            <button onclick="printReport()" class='print_btn float-right'>
                                <i class="mdi mdi-printer " style="font-size: 20px;"></i> Print
                            </button>
                        </div>
                        <div class="card mt-2">
                            <div class="card-body pt-3">
                                <div class="container-fluid" style="padding-left: 25px;padding-right: 25px;">
                                    <div class="row">
                                        <div class="col-12">
                                            <svg height="50" viewBox="0 0 329 122" fill="none" xmlns="http://www.w3.org/2000/svg">
                                                <path d="M92.5401 54.3401H66.4245C61.5988 54.3401 57.0569 56.3239 53.9344 60.0081C53.6506 60.2915 53.0828 60.2915 52.5151 60.0081C49.3926 56.3239 44.8507 54.3401 40.025 54.3401H13.9094V67.9433H92.5401V54.3401Z" fill="#C274BF"/>
                                                <path d="M103.043 54.3401H92.5401V14.9476C83.4564 15.7978 75.2243 19.7654 69.2632 26.567L60.1795 37.0528C56.4892 41.3037 50.2442 41.3037 46.5539 37.0528L37.4702 26.567C31.2252 19.7654 22.9931 15.5144 13.9094 14.9476V54.3401H3.40639C1.41933 54.3401 0 52.6397 0 50.9393V4.17843C0 2.47804 1.70319 0.777641 3.40639 0.777641H11.3546C25.264 0.777641 38.8896 6.72903 47.9733 17.4982L53.3667 23.733L58.7601 17.4982C67.8438 7.01243 81.4694 0.777641 95.3788 0.777641H103.043C105.03 0.777641 106.45 2.47804 106.45 4.17843V50.6559C106.733 52.6397 105.03 54.3401 103.043 54.3401Z" fill="#007588"/>
                                                <path d="M106.45 71.9109C106.733 69.9271 105.03 68.2267 103.043 68.2267H92.5401C92.5401 89.765 74.9405 107.336 53.3667 107.336C31.7929 107.336 14.1933 89.765 14.1933 68.2267H3.69026C1.7032 68.2267 7.48038e-06 69.9271 0.283873 71.9109C2.27093 99.4006 25.264 121.222 53.3667 121.222C81.4694 121.222 104.462 99.4006 106.45 71.9109Z" fill="#007588"/>
                                                <path d="M217.441 84.3804V46.9717C217.441 38.1863 211.196 33.3686 203.248 33.3686C202.964 33.3686 202.68 33.3686 202.396 33.3686C193.88 33.652 187.351 40.7369 187.351 48.9555C187.351 57.7409 187.351 73.6113 187.351 80.6963C187.351 82.68 185.648 84.3804 183.661 84.3804H175.713V46.9717C175.713 38.1863 169.468 33.3686 161.236 33.3686C160.952 33.3686 160.668 33.3686 160.384 33.3686C152.152 33.652 145.623 40.7369 145.623 48.9555V80.1295C145.623 82.3966 143.636 84.3804 141.365 84.3804H133.984V25.7168H141.081C143.068 25.7168 144.771 27.4172 144.771 29.401V32.235C148.462 27.7006 155.274 23.4496 164.642 23.4496C173.442 23.4496 181.106 27.1338 184.513 34.2188C189.622 27.7006 196.435 23.4496 206.654 23.4496C219.712 23.4496 229.079 31.3848 229.079 44.9879V80.9796C229.079 82.9634 227.376 84.6638 225.389 84.6638H217.441V84.3804Z" fill="#007588"/>
                                                <path d="M274.214 82.3967C274.214 84.6638 272.227 86.0808 270.24 86.0808C252.924 84.6638 245.544 73.3279 245.544 56.6073V35.6358H235.324V25.7168H245.544V7.57922H253.492C255.479 7.57922 257.182 9.27962 257.182 11.2634V25.4334H273.078V35.069H257.182V56.3239C257.182 67.9433 261.724 75.5951 274.498 75.5951L274.214 82.3967Z" fill="#007588"/>
                                                <path d="M291.246 53.2065C291.246 39.0365 301.465 31.1014 318.213 31.1014C321.619 31.1014 325.31 31.3848 328.432 32.235L328.716 25.7168C328.716 23.733 327.297 22.0326 325.594 21.7492C322.755 21.4658 319.916 21.1824 315.942 21.1824C294.368 21.1824 278.756 32.8018 278.756 54.0567C278.756 75.3117 294.368 86.931 315.942 86.931C319.632 86.931 322.755 86.6476 325.594 86.3642C327.581 86.0808 329 84.3804 328.716 82.3966L328.432 75.8785C325.31 76.4453 321.619 77.0121 318.213 77.0121C301.181 77.0121 291.246 69.0769 291.246 54.9069" fill="#007588"/>
                                            </svg>
                                        </div>
                                    </div>
                                    <div class="row mt-2" style="padding-top:25px">
                                        <div class="col-12" style="padding-bottom:25px">
                                            <h5 class="headTitle" style="background-color: #007588;">Arrival of Pick Up Location</h5>
                                        </div>
                                        <div class="bacGray row">
                                            <div class="col-3 textContent">
                                                <h5>Requestor</h5>
                                                <p>{{ $task->client ? $task->client->english_name : '' }}</p>
                                            </div>
                                            <div class="col-3 textContent">
                                                <h5>Billed To</h5>
                                                <p>{{ $task->client ? $task->client->english_name : '' }}</p>
                                            </div>
                                            <div class="col-3 textContent">
                                                <h5>Pick Up Location</h5>
                                                <p>{{ $task->from->name }}</p>
                                            </div>
                                            <div class="col-3 textContent">
                                                <h5>Delivery Location</h5>
                                                <p>{{ $task->to->name }}</p>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="row mt-2" style="padding-top:25px">
                                        <div class="col-12" style="padding-bottom:25px">
                                            <h5 class="headTitle" style="background-color: #0A576A;">Task Information</h5>
                                        </div>
                                        <div class="bacGray row">
                                            <div class="col-4 textContent">
                                                <h5>Task Creation Date</h5>
                                                <p>{{ $task->created_at }}</p>
                                            </div>
                                            <div class="col-4 textContent">
                                                <h5>Type of Request</h5>
                                                <p>{{ ucfirst(str_replace('_', ' ', $task->type)) }}</p>
                                            </div>
                                            <div class="col-4 textContent">
                                                <h5>Bag QTY</h5>
                                                @if(isset($task->box_count))
                                                <p>{{ $task->box_count }}</p>
                                                @else
                                                <p>{{ $bag_count }}</p>
                                                @endif
                                            </div>
                                            <div class="col-4 textContent">
                                                <h5>Receiving Date</h5>
                                                <p>{{ $task->created_at }}</p>
                                            </div>
                                            <div class="col-4 textContent">
                                                <h5>Driver Name</h5>
                                                <p>
                                                    @if (!empty($task->driver))
                                                        {{ $task->driver->name }}
                                                    @endif
                                                </p>
                                            </div>
                                            <div class="col-4 textContent">
                                                <h5>Sample QTY</h5>
                                                @if(isset($task->sample_count))
                                                <p>{{ $task->sample_count }}</p>
                                                @else
                                                <p>{{ $sample_count }}</p>
                                                @endif
                                            </div>
                                        </div>
                                    </div>
                                    <div class="leftLine">
                                        <svg width="25" viewBox="0 0 49 836" fill="none" xmlns="http://www.w3.org/2000/svg">
                                            <line x1="23" y1="803" x2="23" y2="46" stroke="#007588" stroke-width="4"/>
                                            <circle cx="24" cy="24" r="24" fill="#007588"/>
                                            <circle cx="24" cy="24" r="12" fill="white"/>
                                            <circle cx="24" cy="418" r="24" fill="#007588"/>
                                            <circle cx="25" cy="812" r="24" fill="#007588"/>
                                        </svg>
                                    </div>
                                    <div class="row mt-2" style="padding-top:25px">
                                        <div class="col-12" style="padding-bottom:25px">
                                            <h5 class="headTitle" style="background-color: #BD6BA7;">Collection Information</h5>
                                        </div>
                                        <div class="col-12" style="padding-bottom:15px">
                                            <div style="width: 55%;">
                                                <svg viewBox="0 0 1156 40" fill="none" xmlns="http://www.w3.org/2000/svg">
                                                    <line x1="33.3547" y1="19" x2="1138.46" y2="18.9999" stroke="#BD6BA7" stroke-width="2"/>
                                                    <circle cx="1140" cy="20" r="16" fill="#BD6BA7"/>
                                                    <circle cx="20" cy="20" r="18" fill="#FCFEFF" stroke="#BD6BA7" stroke-width="4"/>
                                                    <circle cx="20" cy="20" r="8" fill="#BD6BA7"/>
                                                </svg>
                                            </div>
                                        </div>
                                        <div class="col-6 textContent">
                                            <div class="row">
                                                <div class="col-4">
                                                    <h5 class="sameBlock noMT">Arrival of Pick Up Location</h5>
                                                </div>
                                                <div class="col-8">
                                                    <p class="sameBlock">{{ $task->from_location_arrival_time }}</p>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="col-6 textContent">
                                            <div class="col-12">
                                                <div class="row" style="margin-bottom: -15px;">
                                                    <div class="col-5">
                                                        <h5 class="sameBlock noMT" style="padding-right: 15px;">Departure of Pick Up Location</h5>
                                                    </div>
                                                    <div class="col-7">
                                                        <p class="sameBlock">{{ $task->collection_date }}</p>
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="col-12">
                                                <div class="row">
                                                    <div class="col-5">
                                                        <h5 class="sameBlock noMT" style="padding-right: 15px;">Duration of Pick Up</h5>
                                                    </div>
                                                    <div class="col-7">
                                                        <p class="sameBlock">{{ round((strtotime($task->collection_date) - strtotime($task->from_location_arrival_time)) / 60) }} Minute(s)</p>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="row mt-2" style="padding-top:10px">
                                        <div class="col-12" style="padding-bottom:25px">
                                            <h5 class="headTitle" style="background-color: #BD6BA7;">Sample Placement Information</h5>
                                        </div>
                                        <div class="col-12" style="padding-bottom:15px">
                                            <div style="width: 55%;">
                                                <svg viewBox="0 0 1156 40" fill="none" xmlns="http://www.w3.org/2000/svg">
                                                    <line x1="33.3547" y1="19" x2="1138.46" y2="18.9999" stroke="#BD6BA7" stroke-width="2"/>
                                                    <circle cx="1140" cy="20" r="16" fill="#BD6BA7"/>
                                                    <circle cx="20" cy="20" r="18" fill="#FCFEFF" stroke="#BD6BA7" stroke-width="4"/>
                                                    <circle cx="20" cy="20" r="8" fill="#BD6BA7"/>
                                                </svg>
                                            </div>
                                        </div>
                                        <div class="col-6 textContent">
                                            <div class="row">
                                                <div class="col-3">
                                                    <h5 class="sameBlock noMT">Sample Receiving</h5>
                                                </div>
                                                <div class="col-9">
                                                    <p class="sameBlock">{{ $task->collection_date }}</p>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="col-6 textContent">
                                            <div class="col-12">
                                                <div class="row" style="margin-bottom: -15px;">
                                                    <div class="col-3">
                                                        <h5 class="sameBlock noMT" style="padding-right: 15px;">Sample In</h5>
                                                    </div>
                                                    <div class="col-9">
                                                        <p class="sameBlock">{{ $task->freezer_date }}</p>
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="col-12">
                                                <div class="row">
                                                    <div class="col-3">
                                                        <h5 class="sameBlock noMT" style="padding-right: 15px;">Duration</h5>
                                                    </div>
                                                    <div class="col-9">
                                                        <p class="sameBlock">{{ round((strtotime($task->freezer_date) - strtotime($task->collection_date)) / 60) }} Minute(s)</p>
                                                    </div>
                                                </div>
                                                
                                            </div>
                                        </div>
                                    </div>
                                    <div class="row mt-2" style="padding-top:10px">
                                        <div class="col-12" style="padding-bottom:25px">
                                            <h5 class="headTitle" style="background-color: #BD6BA7;">Sample Delivery</h5>
                                        </div>
                                        <div class="col-12" style="padding-bottom:15px">
                                            <div style="width: 55%;">
                                                <svg viewBox="0 0 1156 40" fill="none" xmlns="http://www.w3.org/2000/svg">
                                                    <line x1="33.3547" y1="19" x2="1138.46" y2="18.9999" stroke="#BD6BA7" stroke-width="2"/>
                                                    <circle cx="1140" cy="20" r="16" fill="#BD6BA7"/>
                                                    <circle cx="20" cy="20" r="18" fill="#FCFEFF" stroke="#BD6BA7" stroke-width="4"/>
                                                    <circle cx="20" cy="20" r="8" fill="#BD6BA7"/>
                                                </svg>
                                            </div>
                                        </div>
                                        <div class="col-6 textContent">
                                            <div class="row">
                                                <div class="col-2">
                                                    <h5 class="sameBlock noMT">Sample Out</h5>
                                                </div>
                                                <div class="col-10">
                                                    <p class="sameBlock">{{ $task->freezer_out_date }}</p>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="col-6 textContent">
                                            <div class="col-12">
                                                <div class="row" style="margin-bottom: -15px;">
                                                    <div class="col-4">
                                                        <h5 class="sameBlock noMT" style="padding-right: 15px;">Sample Delivery</h5>
                                                    </div>
                                                    <div class="col-8">
                                                        <p class="sameBlock">{{ $task->close_date }}</p>
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="col-12">
                                                <div class="row">
                                                    <div class="col-4">
                                                        <h5 class="sameBlock noMT" style="padding-right: 15px;">Duration</h5>
                                                    </div>
                                                    <div class="col-8">
                                                        <p class="sameBlock">{{ round((strtotime($task->close_date) - strtotime($task->freezer_out_date)) / 60) }} Minute(s)</p>
                                                    </div>
                                                </div>
                                                
                                            </div>
                                        </div>
                                    </div>
                                    @if (count($bags) > 0)
                                    <div class="row mt-2" style="padding-top:25px">
                                        <div class="bacGray row">
                                            <div class="col-2 textContent">
                                                <h5>Bag Code</h5>
                                            </div>
                                            <div class="col-2 textContent">
                                                <h5>BAGS #</h5>
                                            </div>
                                            <div class="col-4 textContent">
                                                <h5>SAMPLE #</h5>
                                            </div>
                                            <div class="col-2 textContent">
                                                <h5>TYPE</h5>
                                            </div>
                                            <div class="col-2 textContent">
                                                <h5>TEMPERATURE</h5>
                                            </div>
                                            @foreach ($bags as $key => $bag)
                                                <div class="col-2 textContent">
                                                    <p>{{ $key }}</p>
                                                </div>
                                                <div class="col-2 textContent">
                                                    <p>{{ $loop->iteration }}</p>
                                                </div>
                                                <div class="col-4 textContent">
                                                    <p>
                                                        {{ count($bag) }}
                                                        @foreach ($bag as $sample)
                                                            [{{ $sample->barcode_id }}]
                                                        @endforeach
                                                    </p>
                                                </div>
                                                <div class="col-2 textContent">
                                                    <p>{{ $bag[0]->sample_type }}</p>
                                                </div>
                                                <div class="col-2 textContent">
                                                    <p>
                                                        @if ($bag[0]->temperature_type == 'ROOM')
                                                            @if(isset($carTracking->cnt) && $carTracking->cnt > 0)
                                                                @if(!empty($carTracking->total_temp_1) && ($carTracking->total_temp_1/$carTracking->cnt) >= 15 && ($carTracking->total_temp_1/$carTracking->cnt) <= 25)
                                                                    {{ round($carTracking->total_temp_1/$carTracking->cnt,2).' °C' }}
                                                                @elseif(!empty($carTracking->total_temp_2) && ($carTracking->total_temp_2/$carTracking->cnt) >= 15 && ($carTracking->total_temp_2/$carTracking->cnt) <= 25)
                                                                    {{ round($carTracking->total_temp_2/$carTracking->cnt,2).' °C' }}
                                                                @elseif(!empty($carTracking->total_temp_3) && ($carTracking->total_temp_3/$carTracking->cnt) >= 15 && ($carTracking->total_temp_3/$carTracking->cnt) <= 25)
                                                                    {{ round($carTracking->total_temp_3/$carTracking->cnt,2).' °C' }}
                                                                @else
                                                                    +15C TO +25C
                                                                @endif
                                                            @else
                                                                +15C TO +25C
                                                            @endif
                                                        @elseif ($bag[0]->temperature_type == 'REFRIGERATE')
                                                            @if(isset($carTracking->cnt) && $carTracking->cnt > 0)
                                                                @if(!empty($carTracking->total_temp_1) && ($carTracking->total_temp_1/$carTracking->cnt) >= 2 && ($carTracking->total_temp_1/$carTracking->cnt) <= 8)
                                                                    {{ round($carTracking->total_temp_1/$carTracking->cnt,2).' °C' }}
                                                                @elseif(!empty($carTracking->total_temp_2) && ($carTracking->total_temp_2/$carTracking->cnt) >= 2 && ($carTracking->total_temp_2/$carTracking->cnt) <= 8)
                                                                    {{ round($carTracking->total_temp_2/$carTracking->cnt,2).' °C' }}
                                                                @elseif(!empty($carTracking->total_temp_3) && ($carTracking->total_temp_3/$carTracking->cnt) >= 2 && ($carTracking->total_temp_3/$carTracking->cnt) <= 8)
                                                                    {{ round($carTracking->total_temp_3/$carTracking->cnt,2).' °C' }}
                                                                @else
                                                                    +2C TO +8C
                                                                @endif
                                                            @else
                                                                +2C TO +8C
                                                            @endif
                                                        @elseif ($bag[0]->temperature_type == 'FROZEN')
                                                            @if(isset($carTracking->cnt) && $carTracking->cnt > 0)
                                                                @if(!empty($carTracking->total_temp_1) && ($carTracking->total_temp_1/$carTracking->cnt) >= -18 && ($carTracking->total_temp_1/$carTracking->cnt) <= 0)
                                                                    {{ round($carTracking->total_temp_1/$carTracking->cnt,2).' °C' }}
                                                                @elseif(!empty($carTracking->total_temp_2) && ($carTracking->total_temp_2/$carTracking->cnt) >= -18 && ($carTracking->total_temp_2/$carTracking->cnt) <= 0)
                                                                    {{ round($carTracking->total_temp_2/$carTracking->cnt,2).' °C' }}
                                                                @elseif(!empty($carTracking->total_temp_3) && ($carTracking->total_temp_3/$carTracking->cnt) >= -18 && ($carTracking->total_temp_3/$carTracking->cnt) <= 0)
                                                                    {{ round($carTracking->total_temp_3/$carTracking->cnt,2).' °C' }}
                                                                @else
                                                                    0C TO -18C
                                                                @endif
                                                            @else
                                                                0C TO -18C
                                                            @endif
                                                        @endif
                                                    </p>
                                                </div>
                                            @endforeach
                                        </div>
                                    </div>
                                    @endif
                                    <div class="row mt-2" style="padding-top:25px">
                                        <div class="bacGray row">
                                            <div class="col-4 textContent">
                                                <h5>Arrival of Pick Up Location</h5>
                                                <p>{{ $task->from_location_arrival_time }}</p>
                                            </div>
                                            <div class="col-8 textContent">
                                                <h5>Departure of Pick Up Location</h5>
                                                <p>{{ $task->close_date }}</p>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="row mt-2" style="padding-top:25px">
                                        <div class="col-12 text-center">
                                            <canvas id="tempChart" width="400" height="100"></canvas>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

@endsection


@section('script')
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.1.1/jquery.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script>
        const ctx = document.getElementById('tempChart').getContext('2d');
        const tempChart = new Chart(ctx, {
            type: 'line',
            data: {
                labels: @json($labels),
                datasets: [{
                    label: 'Refrigeration',
                    data: @json($temp1),
                    borderColor: 'red',
                    fill: false,
                    tension: 0.1
                },
                {
                    label: 'Freezing',
                    data: @json($temp2),
                    borderColor: 'blue',
                    fill: false,
                    tension: 0.1
                },
                {
                    label: 'Room Temp',
                    data: @json($temp3),
                    borderColor: 'green',
                    fill: false,
                    tension: 0.1
                }]
            },
            options: {
                responsive: true,
                scales: {
                    x: {
                        title: {
                            display: true,
                            text: 'Time'
                        },

            ticks: {
                autoSkip: true,
                maxTicksLimit: 24 // Adjust depending on chart width
            }
                    },
                    y: {
                        title: {
                            display: true,
                            text: 'Temperature (°C)'
                        }
                    }
                }
            }
        });
    </script>
    <script>
        var task = <?php print_r(json_encode($task)); ?>;
        function printReport() {
            const canvas = document.getElementById("tempChart");

            if (!canvas) {
                alert("Chart not found");
                return;
            }

            // Wait for chart to be fully rendered before converting to image
            setTimeout(() => {
                const imgData = canvas.toDataURL("image/png");

                // Clone the print area
                const prtContent = document.getElementById("print_area").cloneNode(true);


                // Replace canvas with image in cloned content
                const canvasToReplace = prtContent.querySelector("#tempChart");
                if (canvasToReplace) {
                    const img = new Image();
                    img.src = imgData;
                    img.style.width = "100%";
                    img.style.maxWidth = "600px"; // Optional for print clarity
                    canvasToReplace.parentNode.replaceChild(img, canvasToReplace);
                }

                const WinPrint = window.open('', '_blank');
                WinPrint.document.write(`
                    <html>
                    <head>
                        <title>Print</title>
                        <link rel="preconnect" href="https://fonts.googleapis.com">
                        <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
                        <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:ital,wght@0,400..900;1,400..900&family=Readex+Pro:wght@160..700&display=swap" rel="stylesheet">
                        <link href="/build/css/bootstrap.min.css" id="bootstrap-style" rel="stylesheet" type="text/css" />
                        <style>
                            .headTitle {
                                font-family: "Playfair Display", sans-serif !important;
                                font-weight: 600;
                                font-size: 16px;
                                color: #fff;
                                width: 380px;
                                padding: 10px;
                                border-radius: 10px;
                                padding-left: 20px;
                                padding-bottom: 15px;
                            }
                            .textContent h5 {
                                font-family: "Readex Pro", sans-serif !important;
                                font-weight: 500;
                                font-size: 10px;
                                margin-top: 1rem;
                            }
                            .noMT {
                                margin-top: unset !important;
                            }
                            .textContent p {
                                font-family: "Readex Pro", sans-serif !important;
                                font-weight: 200;
                                font-size: 10px;
                            }
                            .bacGray {
                                background: #f2f2f2;
                                border-radius: 15px;
                                margin-right: 10px;
                                margin-left: 10px;
                            }
                            .sameBlock {
                                display: inline-block;
                            }
                            .leftLine {                
                                position: absolute;
                                left: 10px;
                                margin-top: 46px;
                            }
                            .leftLine svg {
                                width: 21px
                            }
                            @page { size: auto; margin: 20mm; }
                            .print_btn { display: none; }
                            img { display: block; margin: 0 auto; }
                        </style>
                    </head>
                    <body>
                        <div id="barcode_area">
                            ${prtContent.innerHTML}
                        </div>
                    </body>
                    </html>
                `);
                WinPrint.document.close();

                // Give browser time to load image
                WinPrint.onload = function () {
                    WinPrint.focus();
                    WinPrint.print();
                    WinPrint.close();
                };

            }, 500); // Small delay to ensure chart is fully rendered
        }
    </script>
@endsection

@extends('layouts.master')
@section('content')
    <style>
        #barcode_area svg,
        #barcode_area img {
            margin: 0 auto;
        }

        #barcode_area svg {
            margin-bottom: 10px;
        }

        text#code {
            font-size: 15px;
            margin-bottom: -3px !important;
        }
    </style>

    <div class="card modern-filter-card">
        <div class="card-header">
            <h4 class="card-title mb-0">{{ trans('global.generate') }} {{ trans('cruds.barcode.title_singular') }}</h4>
        </div>

        <div class="card-body">
            <form method="POST" action="{{ route('admin.barcodes.generateBarcodes') }}" enctype="multipart/form-data">
                @csrf
                <div class="row">
                    <div class="col-lg-6 mb-3">
                        <label for="range">Total Count</label>
                        <input type="number" name="range" class="form-control" id="range"
                            placeholder="How many barcode to generate?">
                    </div>
                    <div class="col-lg-6 mb-3">
                        <label for="type">Type</label>
                        <select name="type" id="type" class="form-control">
                            <option value="bag" {{ ($type ?? 'bag') === 'bag' ? 'selected' : '' }}>Bag</option>
                            <option value="sample" {{ ($type ?? 'bag') === 'sample' ? 'selected' : '' }}>Sample</option>
                        </select>
                    </div>
                </div>

                <div class="col-lg-12 d-flex justify-content-end flex-wrap mt-2" style="gap: 10px;">
                    <button type="button" class="btn btn-create mb-1" onclick="printReport()">
                        <i class="mdi mdi-printer"></i> Print Barcode
                    </button>
                    <button type="submit" class="btn btn-save mb-1">
                        <i class="mdi mdi-barcode-scan"></i> Generate Barcode
                    </button>
                </div>
            </form>

            <div id="barcode_area" class="text-center">
                @for ($i = $start; $i < $start + $sequence; $i++)
                    @if ($type == 'location')
                        <div wire:key="{{ uniqid() }}" class="pt-2 ">
                            {!! DNS1D::getBarcodeSVG($sequence . '-' . $type, 'C128', 4, 55) !!}
                        </div>
                    @elseif ($type == 'container')
                        <div wire:key="{{ uniqid() }}" class="pt-2">
                            {!! DNS1D::getBarcodeSVG($sequence . '-' . $type, 'C128', 4, 55) !!}
                        </div>
                    @elseif ($type == 'bag')
                        <div wire:key="{{ uniqid() }}" class="pt-2">
                            {!! DNS1D::getBarcodeSVG($i . '-' . $type, 'C128', 6, 280) !!}
                        </div>
                    @else
                        <div wire:key="{{ uniqid() }}" class="pt-5">
                            {!! DNS1D::getBarcodeSVG(str_pad($i, 10, '0', STR_PAD_LEFT), 'C128', 4, 55) !!}
                        </div>
                    @endif
                @endfor
            </div>
        </div>
    </div>
@endsection


@section('script')
    <script>
        function printReport() {
            var prtContent = document.getElementById("barcode_area");
            var WinPrint = window.open();
            WinPrint.document.write(
                `<div id='barcode_area' style='width:100%;margin-top:50px;margin:0 auto; text-align:center'>
        <style>@page {    size:  auto; 
    margin-top: 0.04in;
    margin-left: 0.68in;
    margin-right: 0.56in;
    margin-bottom: 0.03in;} svg{margin-top:180px}</style>`

                +
                prtContent.innerHTML +
                `</div>`
            );
            WinPrint.document.close();
            WinPrint.focus();
            WinPrint.print();
            WinPrint.close();

        };
    </script>
@endsection

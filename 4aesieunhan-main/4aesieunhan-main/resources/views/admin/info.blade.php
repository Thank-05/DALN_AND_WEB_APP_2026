@extends('admin.layout.index')

@section('content')
@can('movies')
<div class="container-fluid py-4">
    <div class="row">
        <div class="col-md-12">
            <div class="card">
                <form method="post" action="{{ url('admin/info') }}" enctype="multipart/form-data">
                    @csrf

                    <div class="card-header pb-0">
                        <div class="d-flex align-items-center">
                            <p class="mb-0">@lang('lang.information')</p>
                            <button type="submit" class="btn bg-gradient-primary btn-sm ms-auto">@lang('lang.submit')</button>
                        </div>
                    </div>

                    <div class="card-body">
                        @if (session('success'))
                            <div class="alert alert-success text-white">{{ session('success') }}</div>
                        @endif

                        <div class="row">
                            {{-- Logo --}}
                            <div class="col-md-12">
                                <div class="form-group file-uploader">
                                    <label for="infoImage">@lang('lang.image')</label>
                                    <input id="infoImage" type="file" name="Image" accept="image/*"
                                           class="form-control image-movie @error('Image') is-invalid @enderror">
                                    @error('Image')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror

                                    <img style="width: 300px; margin-top: 10px"
                                         class="img_movie {{ empty($info['logo']) ? 'd-none' : '' }}"
                                         src="{{ !empty($info['logo']) ? asset('images/favicon/' . $info['logo']) : '' }}"
                                         alt="Logo">
                                </div>
                            </div>

                            {{-- Tên --}}
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label for="infoName">@lang('lang.name')</label>
                                    <input id="infoName" name="name" type="text"
                                           class="form-control @error('name') is-invalid @enderror"
                                           value="{{ old('name', $info['name'] ?? '') }}">
                                    @error('name')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>

                            {{-- Số điện thoại --}}
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label for="infoPhone">@lang('lang.phone')</label>
                                    <input id="infoPhone" name="phone" type="tel"
                                           class="form-control @error('phone') is-invalid @enderror"
                                           value="{{ old('phone', $info['phone'] ?? '') }}">
                                    @error('phone')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>

                            {{-- Email --}}
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label for="infoEmail">Email</label>
                                    <input id="infoEmail" name="email" type="email"
                                           class="form-control @error('email') is-invalid @enderror"
                                           value="{{ old('email', $info['email'] ?? '') }}">
                                    @error('email')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>

                            {{-- Facebook --}}
                            <div class="col-md-3">
                                <div class="form-group">
                                    <label for="infoFacebook">Facebook</label>
                                    <input id="infoFacebook" name="facebook" type="text" class="form-control"
                                           value="{{ old('facebook', $info['facebook'] ?? '') }}">
                                </div>
                            </div>

                            {{-- Twitter --}}
                            <div class="col-md-3">
                                <div class="form-group">
                                    <label for="infoTwitter">Twitter</label>
                                    <input id="infoTwitter" name="twitter" type="text" class="form-control"
                                           value="{{ old('twitter', $info['twitter'] ?? '') }}">
                                </div>
                            </div>

                            {{-- Instagram --}}
                            <div class="col-md-3">
                                <div class="form-group">
                                    <label for="infoInstagram">Instagram</label>
                                    <input id="infoInstagram" name="instagram" type="text" class="form-control"
                                           value="{{ old('instagram', $info['instagram'] ?? '') }}">
                                </div>
                            </div>

                            {{-- Youtube --}}
                            <div class="col-md-3">
                                <div class="form-group">
                                    <label for="infoYoutube">Youtube</label>
                                    <input id="infoYoutube" name="youtube" type="text" class="form-control"
                                           value="{{ old('youtube', $info['youtube'] ?? '') }}">
                                </div>
                            </div>

                            {{-- Giờ làm việc --}}
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="infoWorktime">@lang('lang.worktime')</label>
                                    <input id="infoWorktime" name="worktime" type="text" class="form-control"
                                           value="{{ old('worktime', $info['worktime'] ?? '') }}">
                                </div>
                            </div>

                            {{-- Copyright --}}
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="infoCopyright">Copyright</label>
                                    <input id="infoCopyright" name="copyright" type="text" class="form-control"
                                           value="{{ old('copyright', $info['copyright'] ?? '') }}">
                                </div>
                            </div>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
@else
<h1 class="text-center">Permissions Deny</h1>
@endcan
@endsection

@section('scripts')
<script>
    function readURL(input) {
        if (input.files && input.files[0]) {
            var reader = new FileReader();
            reader.onload = function (e) {
                $(input).closest('.file-uploader').find('.img_movie')
                    .attr('src', e.target.result)
                    .removeClass('d-none');
            };
            reader.readAsDataURL(input.files[0]);
        }
    }

    $(document).on('change', '.image-movie', function () {
        readURL(this);
    });
</script>
@endsection
<?php

use EvolutionCMS\Facades\ManagerTheme;

?>
<div class="tab-page {{ $tabPageName }}" id="{{ $tabIndexPageName }}">
    <h2 class="tab">
        <a href="?a=86&tab={{ $index }}">
            <i class="fa fa-user-tag"></i>{{ __('global.manage_permission') }}
        </a>
    </h2>
    <script>tpResources.addTabPage(document.getElementById('{{ $tabIndexPageName }}'))</script>

    <div id="_actions">
        <form class="btn-group form-group form-inline">
            @csrf
            <div class="input-group input-group-sm">
                <input class="form-control filterElements-form" type="text" id="{{ $tabIndexPageName }}_search"
                       size="30" placeholder="{{ __('global.element_filter_msg') }}"/>
                <div class="input-group-btn">
                    <a class="btn btn-success"
                       href="{{ (new EvolutionCMS\Models\Permissions)->makeUrl('actions.new') }}">
                        <i class="{{ ManagerTheme::getStyle('icon_add') }}"></i>
                        <span>{{ __('global.new_permission') }}</span>
                    </a>
                </div>
            </div>
        </form>
    </div>

    <div class="clearfix"></div>
    <div class="panel-group no-transition">
        <div id="{{ $tabIndexPageName }}_content" class="resourceTable panel panel-default">
            @if(isset($outCategory) && $outCategory->count() > 0)
                @component('manager::partials.panelCollapse', ['name' => $tabIndexPageName . '_content', 'id' => 0, 'title' => __('global.no_category')])
                    <ul class="elements">
                        @foreach($outCategory as $item)
                            @include('manager::page.user_roles.element_permission', compact('item', 'tabIndexPageName'))
                        @endforeach
                    </ul>
                @endcomponent
            @endif

            @if(isset($groups))
                @foreach($groups as $group)
                    @component('manager::partials.panelCollapse', ['name' => $tabIndexPageName . '_content', 'id' => $group->id, 'title' => __('global.' . $group->lang_key) ])
                        <ul class="elements">
                            @foreach($group->permissions as $item)
                                @include('manager::page.user_roles.element_permission', compact('item', 'tabIndexPageName'))
                            @endforeach
                        </ul>
                    @endcomponent
                @endforeach
            @endif
        </div>
    </div>
    <div class="clearfix"></div>
</div>

@push('scripts.bot')
    <script>
      initQuicksearch('{{ $tabIndexPageName }}_search', '{{ $tabIndexPageName }}_content')
      initViews('ch', '{{ $tabIndexPageName }}', '{{ $tabIndexPageName }}_content')
    </script>
@endpush

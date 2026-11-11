@props([
    'vlogs',
    'cols' => 'sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4',
    'showCreator' => true,
    'showCategory' => true,
])

<div {{ $attributes->merge(['class' => 'grid grid-cols-1 gap-x-5 gap-y-8 '.$cols]) }}>
    @foreach ($vlogs as $vlog)
        <x-vlog-card :vlog="$vlog" :show-creator="$showCreator" :show-category="$showCategory" />
    @endforeach
</div>

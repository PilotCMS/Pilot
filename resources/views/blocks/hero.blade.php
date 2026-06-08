<div class="hero-section bg-gradient-to-r from-blue-600 to-purple-600 text-white py-20 px-8 md:px-12 rounded-xl">
    @php
        $backgroundFocalX = $data['background_image_focal_x'] ?? 50;
        $backgroundFocalY = $data['background_image_focal_y'] ?? 50;
        $backgroundImage = $data['background_image'] ?? null;
        $backgroundImage = is_array($backgroundImage) ? ($backgroundImage['en'] ?? reset($backgroundImage) ?: null) : $backgroundImage;
        $title = $data['title'] ?? 'Hero Title';
        $title = is_array($title) ? ($title['en'] ?? reset($title) ?: 'Hero Title') : $title;
        $subtitle = $data['subtitle'] ?? 'Hero subtitle';
        $subtitle = is_array($subtitle) ? ($subtitle['en'] ?? reset($subtitle) ?: 'Hero subtitle') : $subtitle;
    @endphp
    @if($backgroundImage)
        <div class="absolute inset-0 bg-cover opacity-20 rounded-lg" style="background-image: url('{{ $backgroundImage }}'); background-position: {{ $backgroundFocalX }}% {{ $backgroundFocalY }}%;"></div>
    @endif
    <div class="relative space-y-4">
        <h2 class="text-4xl md:text-5xl font-bold">{{ $title }}</h2>
        <p class="text-xl text-white/90">{{ $subtitle }}</p>
    </div>
</div>

<div class="hero-section bg-gradient-to-r from-blue-600 to-purple-600 text-white py-20 px-8 md:px-12 rounded-xl">
    @php
        $backgroundFocalX = $data['background_image_focal_x'] ?? 50;
        $backgroundFocalY = $data['background_image_focal_y'] ?? 50;
    @endphp
    @if($data['background_image'] ?? null)
        <div class="absolute inset-0 bg-cover opacity-20 rounded-lg" style="background-image: url('{{ $data['background_image'] }}'); background-position: {{ $backgroundFocalX }}% {{ $backgroundFocalY }}%;"></div>
    @endif
    <div class="relative space-y-4">
        <h2 class="text-4xl md:text-5xl font-bold">{{ $data['title']['en'] ?? $data['title'] ?? 'Hero Title' }}</h2>
        <p class="text-xl text-white/90">{{ $data['subtitle']['en'] ?? $data['subtitle'] ?? 'Hero subtitle' }}</p>
    </div>
</div>

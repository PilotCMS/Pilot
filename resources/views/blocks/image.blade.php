<div class="image-block">
    @php
        $focalX = $data['image_focal_x'] ?? 50;
        $focalY = $data['image_focal_y'] ?? 50;
        $image = $data['image'] ?? null;
        $image = is_array($image) ? ($image['en'] ?? reset($image) ?: null) : $image;
        $alt = $data['alt'] ?? '';
        $alt = is_array($alt) ? ($alt['en'] ?? reset($alt) ?: '') : $alt;
    @endphp
    @if($image)
        <img src="{{ $image }}" alt="{{ $alt }}" class="rounded-xl w-full object-cover aspect-video" style="object-position: {{ $focalX }}% {{ $focalY }}%;">
    @else
        <div class="border-2 border-dashed border-gray-300 dark:border-gray-600 rounded-xl p-10 text-center text-gray-400 dark:text-gray-500">
            No image selected
        </div>
    @endif
</div>

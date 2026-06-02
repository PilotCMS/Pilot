<div class="image-block">
    @php
        $focalX = $data['image_focal_x'] ?? 50;
        $focalY = $data['image_focal_y'] ?? 50;
    @endphp
    @if($data['image'] ?? null)
        <img src="{{ $data['image'] }}" alt="{{ $data['alt']['en'] ?? $data['alt'] ?? '' }}" class="rounded-xl w-full object-cover aspect-video" style="object-position: {{ $focalX }}% {{ $focalY }}%;">
    @else
        <div class="border-2 border-dashed border-gray-300 dark:border-gray-600 rounded-xl p-10 text-center text-gray-400 dark:text-gray-500">
            No image selected
        </div>
    @endif
</div>

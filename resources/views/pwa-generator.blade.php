<!DOCTYPE html>
<html>
<head>
    <title>PWA Asset Generator</title>
    <meta name="csrf-token" content="{{ csrf_token() }}">
    @vite('resources/css/app.css')
</head>
<body class="p-8">
<div class="max-w-2xl mx-auto">
    <h1 class="text-2xl font-bold mb-4">PWA Helpers for Laravel</h1>

    <h2 class="text-xl font-bold mb-4">Filament AdminPanelProvder</h1>

<pre>
    ->tenant(Tenant::class)
    ->tenantRegistration(RegisterNewTenant::class)
    ->tenantProfile(EditMyTenantProfile::class)
    ->registration()
    ->passwordReset()
    ->tenantMiddleware([
        ApplyTenantScopes::class,
    ], isPersistent: true)
    ->sidebarCollapsibleOnDesktop()
    ->plugin(
        FilamentLaravelLogPlugin::make()
            ->authorize(
                fn () => auth()->user()->role([Role::Administrator])
            )
    )
    ->databaseNotifications()
    ->renderHook(
        'panels::auth.login.form.after',
        fn () => view('auth.socialite.google')
    )
    ->profile(EditProfile::class)
    ->colors([
        'primary' => '#2f4e6f', // Dark blue
    ]);
</pre>
<br>
<hr>
<br>
    <h2 class="text-xl font-bold mb-4">Tenant Model</h1>
<pre>
    class Tenant extends Model
    {
        use HasFactory;
    
        protected $fillable = [
            'name',
        ];
    
        public function users(): BelongsToMany
        {
            return $this->belongsToMany(User::class);
        }
    }
</pre>
<br>
<hr>
<br>
    <h2 class="text-xl font-bold mb-4">User Model</h1>
<pre>
    class User extends Authenticatable implements FilamentUser, HasTenants
    {
        use HasFactory;
        use Notifiable;    
        use HasPushSubscriptions;
    
        protected $fillable = [
            'name',
            'email',
            'password',
            'google_id',
            'active',
        ];
    
        protected $hidden = [
            'password',
            'remember_token',
        ];
    
        protected function casts(): array
        {
            return [
                'role' => Role::class,
                'email_verified_at' => 'datetime',
                'password' => 'hashed',
            ];
        }
    
        public function canAccessPanel(Panel $panel): bool
        {
            return true;
        }
    
        public function tenants(): BelongsToMany
        {
            return $this->belongsToMany(Tenant::class);
        }
    
        public function getTenants(Panel $panel): Collection
        {
            return $this->tenants;
        }
    
        public function canAccessTenant(Model $tenant): bool
        {
            return $this->tenants()->whereKey($tenant)->exists();
        }
    
        // Add relationships...
    }
</pre>
<br>
<hr>
<br>
    <h2 class="text-xl font-bold mb-4">Apply Tenant Scopes</h1>
<pre>
    namespace App\Http\Middleware;
    
    use App\Models\Setting;
    use Closure;
    use Filament\Facades\Filament;
    use Illuminate\Database\Eloquent\Builder;
    use Illuminate\Http\Request;
    use Symfony\Component\HttpFoundation\Response;
    
    class ApplyTenantScopes
    {
        /**
         * Handle an incoming request.
         *
         * @param  Closure(Request): (Response)  $next
         */
        public function handle(Request $request, Closure $next): Response
        {
            Setting::addGlobalScope(
                fn (Builder $query) => $query->whereBelongsTo(Filament::getTenant()),
            );

            // ...
    
            return $next($request);
        }
    }
</pre>
<br>
<hr>
<br>
    <h2 class="text-xl font-bold mb-4">RegisterNewTenant::class</h1>
<pre>
    namespace App\Filament\Pages\Tenancy;

    use App\Models\Tenant;
    use App\Models\User;
    use Filament\Forms\Components\TextInput;
    use Filament\Forms\Form;
    use Filament\Pages\Tenancy\RegisterTenant;
    
    class RegisterNewTenant extends RegisterTenant
    {
        public static function getLabel(): string
        {
            return 'Register';
        }
    
        public function form(Form $form): Form
        {
            return $form
                ->schema([
                    TextInput::make('name')
                        ->helperText('The name of your workspace.')
                        ->columnSpanFull(),
                ]);
        }
    
        protected function handleRegistration(array $data): Tenant
        {
            $tenant = Tenant::create($data);
    
            $tenant->users()->attach(auth()->user());
        
            return $tenant;
        }
    }
    
</pre>
<br>
<hr>
<br>
    <h2 class="text-xl font-bold mb-4">EditExistingTenantProfile::class</h1>
<pre>
    namespace App\Filament\Pages\Tenancy;

    use Filament\Forms\Components\TextInput;
    use Filament\Forms\Form;
    use Filament\Pages\Tenancy\EditTenantProfile;
    
    class EditExistingTenantProfile extends EditTenantProfile
    {
        public static function getLabel(): string
        {
            return 'Workspace';
        }
    
        public function form(Form $form): Form
        {
            return $form
                ->schema([
                    TextInput::make('name'),
                ]);
        }
    }
    
</pre>

    <h1 class="text-2xl font-bold mb-4">PWA Asset Generator</h1>

    <textarea id="svgInput" class="w-full h-32 p-2 mb-4 border rounded" placeholder="Paste your SVG code here..."></textarea>

    <div class="space-y-4">
        <div>
            <h2 class="text-lg font-medium mb-2">Icons</h2>
            <div class="flex flex-wrap gap-2">
                @foreach($iconSizes as $size)
                    <button onclick="generate('icon', {{ $size }})" class="px-3 py-2 bg-blue-500 text-white rounded hover:bg-blue-600">
                        {{ $size }}x{{ $size }}
                    </button>
                @endforeach
            </div>
        </div>

        <div>
            <h2 class="text-lg font-medium mb-2">Splash Screens</h2>
            <div class="flex flex-wrap gap-2">
                @foreach($splashSizes as $size)
                    <button onclick="generate('splash', {{ $size['width'] }}, {{ $size['height'] }})" class="px-3 py-2 bg-green-500 text-white rounded hover:bg-green-600">
                        {{ $size['width'] }}x{{ $size['height'] }}
                    </button>
                @endforeach
            </div>
        </div>
    </div>
</div>

<script>
    function generate(type, width, height = width) {
        const svg = document.getElementById('svgInput').value;
        if (!svg) return;

        fetch('/generate', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
            },
            body: JSON.stringify({ svg, type, width, height })
        })
            .then(response => response.blob())
            .then(blob => {
                const url = window.URL.createObjectURL(blob);
                const a = document.createElement('a');
                a.href = url;
                a.download = type === 'icon' ?
                    `icon-${width}x${width}.png` :
                    `splash-${width}x${height}.png`;
                a.click();
            });
    }
</script>
</body>
</html>

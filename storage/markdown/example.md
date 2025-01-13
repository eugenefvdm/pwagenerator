# Example Code
- [Example Code](#example-code)
- [Filament AdminPanelProvder](#filament-adminpanelprovder)
- [Tenant Model](#tenant-model)
- [User Model](#user-model)
- [Apply Tenant Scopes](#apply-tenant-scopes)
- [RegisterNewTenant::class](#registernewtenantclass)
- [EditExistingTenantProfile::class](#editexistingtenantprofileclass)
- [AppServiceProvider](#appserviceprovider)
- [Manifest.json](#manifestjson)
# Filament AdminPanelProvder
```php
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
```
# Tenant Model
```php
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
```
# User Model
```php
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
```
# Apply Tenant Scopes
```php
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
```
# RegisterNewTenant::class
```php
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
```    
# EditExistingTenantProfile::class
```php
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
```    
# AppServiceProvider
```php
    namespace App\Providers;

    use Filament\Support\Facades\FilamentView;
    use Filament\View\PanelsRenderHook;
    use Illuminate\Database\Eloquent\Model;
    use Illuminate\Support\Facades\Blade;
    use Illuminate\Support\Facades\URL;
    use Illuminate\Support\ServiceProvider;
    
    class AppServiceProvider extends ServiceProvider
    {
        /**
         * Register any application services.
         */
        public function register(): void
        {
            //
        }
    
        /**
         * Bootstrap any application services.
         */
        public function boot(): void
        {
            URL::forceScheme('https');
    
            Model::preventLazyLoading();

            Model::preventSilentlyDiscardingAttributes();
    
            FilamentView::registerRenderHook(
                PanelsRenderHook::HEAD_END,
                fn (): string => Blade::render('<link rel="manifest" href="' . config('app.url') . '/manifest.json">'),
            );
        }
    }
```
# Manifest.json
```json

```



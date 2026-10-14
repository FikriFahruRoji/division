<?php
use App\Models\Department;
use App\Models\Document;
use App\Models\User;
use Illuminate\Support\Facades\Storage;
use Illuminate\Foundation\Testing\RefreshDatabase;

class AuthSecurityReproductionTest extends Tests\TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        config(['view.compiled' => sys_get_temp_dir().'/ttd-security-auth-views', 'logging.default'=>'null']);
        if (!is_dir(config('view.compiled'))) mkdir(config('view.compiled'), 0700, true);
    }

    public function test_public_registration_grants_operator_and_reads_foreign_dashboard(): void
    {
        $dep = Department::factory()->create();
        $owner = User::factory()->withDepartment($dep->id)->create();
        Document::factory()->withCreator($owner)->create(['title'=>'ConfidentialForeignDepartment']);
        $this->post('/register', ['name'=>'Outside User','email'=>'outside@example.com','password'=>'password','password_confirmation'=>'password'])->assertRedirect('/dashboard');
        $user = User::where('email','outside@example.com')->firstOrFail();
        $this->assertSame('operator',$user->role);
        $this->assertSame('active',$user->status);
        $this->assertNull($user->email_verified_at);
        $this->actingAs($user)->get('/dashboard')->assertOk()->assertSee('ConfidentialForeignDepartment');
    }

    public function test_operator_foreign_metadata_leaks_but_document_policy_blocks_detail(): void
    {
        $a=Department::factory()->create(); $b=Department::factory()->create();
        $operator=User::factory()->withDepartment($a->id)->create();
        $owner=User::factory()->withDepartment($b->id)->create();
        $doc=Document::factory()->withCreator($owner)->create(['title'=>'ForeignConfidentialTitle']);
        $this->actingAs($operator)->get('/documents/'.$doc->uuid)->assertForbidden();
        $this->get('/dashboard')->assertOk()->assertSee('ForeignConfidentialTitle')->assertSee($owner->email);
    }

    public function test_unassigned_admin_reaches_other_department_signing_view_but_crashes(): void
    {
        $a=Department::factory()->create(); $b=Department::factory()->create();
        $admin=User::factory()->admin()->withDepartment($a->id)->create();
        $owner=User::factory()->withDepartment($b->id)->create();
        $doc=Document::factory()->withCreator($owner)->create(['title'=>'ForeignSigningMetadata']);
        $this->actingAs($admin)->get('/documents/'.$doc->uuid)->assertForbidden();
        $this->get('/signing/'.$doc->uuid)->assertStatus(500);
    }

    public function test_inactive_password_user_can_login_download_and_revoke(): void
    {
        Storage::fake('local');
        $user=User::factory()->operator()->inactive()->create();
        $doc=Document::factory()->signedValid()->withCreator($user)->create();
        Storage::disk('local')->put($doc->signed_file_path,'%PDF-1.4 synthetic');
        $this->post('/login',['email'=>$user->email,'password'=>'password'])->assertRedirect('/dashboard');
        $this->assertAuthenticatedAs($user);
        $this->get('/documents/'.$doc->uuid.'/download')->assertOk();
        $this->post('/documents/'.$doc->uuid.'/revoke',['reason'=>'Inactive user can still revoke'])->assertRedirect();
        $this->assertSame('revoked',$doc->fresh()->status);
    }

    public function test_unverified_signer_reaches_protected_signing_routes(): void
    {
        $user=User::factory()->signer()->unverified()->create();
        $this->actingAs($user)->get('/signing/pending')->assertOk();
    }

    public function test_last_super_admin_can_delete_self_via_profile(): void
    {
        $user=User::factory()->superAdmin()->create();
        $this->actingAs($user)->delete('/users/'.$user->id)->assertForbidden();
        $this->delete('/profile',['password'=>'password'])->assertRedirect('/');
        $this->assertSame(0,User::where('role','super_admin')->count());
    }

    public function test_document_department_access_tracks_mutable_creator_department(): void
    {
        $a=Department::factory()->create(); $b=Department::factory()->create();
        $adminA=User::factory()->admin()->withDepartment($a->id)->create();
        $adminB=User::factory()->admin()->withDepartment($b->id)->create();
        $owner=User::factory()->withDepartment($a->id)->create();
        $doc=Document::factory()->withCreator($owner)->create();
        $policy=new App\Policies\DocumentPolicy;
        $this->assertTrue($policy->view($adminA,$doc));
        $this->assertFalse($policy->view($adminB,$doc));
        $owner->update(['department_id'=>$b->id]);
        $doc=$doc->fresh();
        $this->assertFalse($policy->view($adminA,$doc));
        $this->assertTrue($policy->view($adminB,$doc));
    }
}

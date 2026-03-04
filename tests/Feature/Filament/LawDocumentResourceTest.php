<?php

namespace Tests\Feature\Filament;

use App\Filament\Resources\LawDocumentResource;
use App\Jobs\ProcessLawDocument;
use App\Models\LawDocument;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Livewire\Livewire;
use Tests\TestCase;

class LawDocumentResourceTest extends TestCase
{
    use RefreshDatabase;

    // ============================================================
    // Authentication
    // ============================================================

    /**
     * Unauthenticated requests to the admin panel are redirected to the Filament login page.
     */
    public function test_unauthenticated_user_is_redirected_to_filament_login(): void
    {
        $response = $this->get('/admin/law-documents');

        $response->assertRedirect(route('filament.admin.auth.login'));
    }

    // ============================================================
    // List Page
    // ============================================================

    /**
     * Authenticated users can see law documents in the table.
     */
    public function test_authenticated_user_can_see_law_documents_in_the_list(): void
    {
        $user = User::factory()->create();
        $document = LawDocument::factory()->create(['title' => 'Companies and Allied Matters Act']);

        Livewire::actingAs($user)
            ->test(LawDocumentResource\Pages\ListLawDocuments::class)
            ->assertCanSeeTableRecords([$document]);
    }

    /**
     * The list page does not show records that were not created.
     */
    public function test_list_does_not_show_records_that_do_not_exist(): void
    {
        $user = User::factory()->create();
        $existing = LawDocument::factory()->create();
        $ghost = LawDocument::factory()->make(['id' => 99999]);

        Livewire::actingAs($user)
            ->test(LawDocumentResource\Pages\ListLawDocuments::class)
            ->assertCanSeeTableRecords([$existing])
            ->assertCanNotSeeTableRecords([$ghost]);
    }

    // ============================================================
    // Reprocess Action
    // ============================================================

    /**
     * The reprocess action resets the document status to pending and dispatches the processing job.
     */
    public function test_reprocess_action_resets_status_and_dispatches_job(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $document = LawDocument::factory()->completed()->create();

        Livewire::actingAs($user)
            ->test(LawDocumentResource\Pages\ListLawDocuments::class)
            ->callTableAction('reprocess', $document);

        $document->refresh();

        $this->assertEquals('pending', $document->status);
        $this->assertEquals(0, $document->chunk_count);

        Queue::assertPushed(ProcessLawDocument::class, function ($job) use ($document) {
            return $job->lawDocumentId === $document->id;
        });
    }

    /**
     * The reprocess action is not available when the document is currently processing.
     */
    public function test_reprocess_action_is_available_for_any_status(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $document = LawDocument::factory()->failed()->create();

        Livewire::actingAs($user)
            ->test(LawDocumentResource\Pages\ListLawDocuments::class)
            ->callTableAction('reprocess', $document);

        $document->refresh();

        $this->assertEquals('pending', $document->status);
        Queue::assertPushed(ProcessLawDocument::class);
    }

    // ============================================================
    // Delete Action
    // ============================================================

    /**
     * The delete action removes the law document from the database.
     */
    public function test_delete_action_removes_the_document(): void
    {
        $user = User::factory()->create();
        $document = LawDocument::factory()->create();

        Livewire::actingAs($user)
            ->test(LawDocumentResource\Pages\ListLawDocuments::class)
            ->callTableAction('delete', $document);

        $this->assertDatabaseMissing('law_documents', ['id' => $document->id]);
    }

    // ============================================================
    // Create Page
    // ============================================================

    /**
     * Submitting the create form with a missing title fails validation.
     */
    public function test_create_form_requires_title(): void
    {
        $user = User::factory()->create();

        Livewire::actingAs($user)
            ->test(LawDocumentResource\Pages\CreateLawDocument::class)
            ->fillForm(['title' => ''])
            ->call('create')
            ->assertHasFormErrors(['title' => 'required']);
    }

    /**
     * Submitting the create form with a missing file fails validation.
     */
    public function test_create_form_requires_pdf_file(): void
    {
        $user = User::factory()->create();

        Livewire::actingAs($user)
            ->test(LawDocumentResource\Pages\CreateLawDocument::class)
            ->fillForm(['title' => 'Test Act', 'file_path' => null])
            ->call('create')
            ->assertHasFormErrors(['file_path' => 'required']);
    }

    // ============================================================
    // Edit Page
    // ============================================================

    /**
     * The edit form is pre-filled with the document's current data.
     */
    public function test_edit_form_is_prefilled_with_existing_data(): void
    {
        $user = User::factory()->create();
        $document = LawDocument::factory()->create([
            'title' => 'Labour Act',
            'category' => 'act',
            'jurisdiction' => 'federal',
        ]);

        Livewire::actingAs($user)
            ->test(LawDocumentResource\Pages\EditLawDocument::class, ['record' => $document->getRouteKey()])
            ->assertFormSet([
                'title' => 'Labour Act',
                'category' => 'act',
                'jurisdiction' => 'federal',
            ]);
    }
}

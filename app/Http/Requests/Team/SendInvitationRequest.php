<?php

declare(strict_types=1);

namespace App\Http\Requests\Team;

use App\Enums\InvitationStatus;
use App\Models\TeamInvitation;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

final class SendInvitationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->id === $this->user()->ownedTeam?->user_id;
    }

    /** @return array<string, array<int, ValidationRule|array<mixed>|string>> */
    public function rules(): array
    {
        return [
            'email' => ['required', 'string', 'email', 'max:255'],
        ];
    }

    public function withValidator(\Illuminate\Validation\Validator $validator): void
    {
        $validator->after(function (\Illuminate\Validation\Validator $validator): void {
            $team = $this->user()->ownedTeam;

            if (! $team) {
                return;
            }

            if ($team->isFull()) {
                $validator->errors()->add('email', 'This team has reached the maximum of 10 members.');
            }

            if ($team->members()->where('users.email', $this->input('email'))->exists()) {
                $validator->errors()->add('email', 'This user is already a member of your team.');
            }

            $hasPendingInvite = TeamInvitation::query()
                ->where('team_id', $team->id)
                ->where('email', $this->input('email'))
                ->where('status', InvitationStatus::Pending)
                ->exists();

            if ($hasPendingInvite) {
                $validator->errors()->add('email', 'A pending invitation already exists for this email.');
            }
        });
    }
}

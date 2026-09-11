<?php

namespace App\Http\Controllers;

use App\Models\BookingConfirmation;
use App\Models\Customer;
use App\Models\Job;
use App\Models\Port;
use App\Models\Vendor;
use Illuminate\Http\Request;

class BookingConfirmationController extends Controller
{
    public function index(Request $request)
    {
        $search = $request->query('search');
        $status = $request->query('status');
        $dateFrom = $request->query('date_from');
        $dateTo = $request->query('date_to');

        $query = BookingConfirmation::with(['customer', 'job', 'creator'])
            ->latest('booking_date')
            ->latest('id');

        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('number', 'like', "%{$search}%")
                    ->orWhere('customer_ref', 'like', "%{$search}%")
                    ->orWhere('shipper_name', 'like', "%{$search}%")
                    ->orWhere('carrier_name', 'like', "%{$search}%")
                    ->orWhere('vessel_voyage', 'like', "%{$search}%")
                    ->orWhereHas('customer', function ($cq) use ($search) {
                        $cq->where('name', 'like', "%{$search}%");
                    })
                    ->orWhereHas('job', function ($jq) use ($search) {
                        $jq->where('number', 'like', "%{$search}%");
                    });
            });
        }

        if ($status) {
            $query->where('status', $status);
        }

        if ($dateFrom) {
            $query->whereDate('booking_date', '>=', $dateFrom);
        }

        if ($dateTo) {
            $query->whereDate('booking_date', '<=', $dateTo);
        }

        $bookingConfirmations = $query->paginate(15)->withQueryString();

        return view('booking-confirmations.index', [
            'bookingConfirmations' => $bookingConfirmations,
            'search'               => $search,
            'status'               => $status,
            'dateFrom'             => $dateFrom,
            'dateTo'               => $dateTo,
        ]);
    }

    public function create(Request $request)
    {
        $selectedJob = null;
        if ($jobId = $request->query('job_id')) {
            $selectedJob = Job::with(['customer', 'quotation'])->find($jobId);
        }

        $jobs = Job::with('customer')->latest('id')->limit(50)->get();
        $customers = Customer::orderBy('name')->get();
        $carriers = Vendor::orderBy('name')->get();
        $ports = Port::orderBy('name')->get();

        $defaultNumber = BookingConfirmation::generateNumber();

        return view('booking-confirmations.create', [
            'selectedJob'   => $selectedJob,
            'jobs'          => $jobs,
            'customers'     => $customers,
            'carriers'      => $carriers,
            'ports'         => $ports,
            'defaultNumber' => $defaultNumber,
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'number'              => 'required|string|max:60|unique:booking_confirmations,number',
            'booking_date'        => 'required|date',
            'job_id'              => 'nullable|exists:jobs,id',
            'customer_id'         => 'required|exists:customers,id',
            'contact_person'      => 'nullable|string|max:120',
            'customer_ref'        => 'nullable|string|max:100',
            'shipper_name'        => 'nullable|string|max:160',
            'carrier_name'        => 'nullable|string|max:160',
            'carrier_booking_no'  => 'nullable|string|max:100',
            'vessel_voyage'       => 'nullable|string|max:120',
            'service_term'        => 'required|string|max:40',
            'pol'                 => 'nullable|string|max:120',
            'pod'                 => 'nullable|string|max:120',
            'etd'                 => 'nullable|date',
            'eta'                 => 'nullable|date',
            'quantity'            => 'nullable|string|max:100',
            'cargo_description'   => 'nullable|string',
            'gross_weight'        => 'nullable|numeric|min:0',
            'volume'              => 'nullable|numeric|min:0',
            'delivery_cargo_to'   => 'nullable|string',
            'doc_cutoff_at'       => 'nullable|date',
            'cy_cutoff_at'        => 'nullable|date',
            'delivery_cutoff_at'  => 'nullable|date',
            'status'              => 'required|string|in:draft,confirmed,cancelled',
            'notes'               => 'nullable|string',
        ]);

        $validated['created_by'] = auth()->id();

        $bc = BookingConfirmation::create($validated);

        return redirect()->route('booking-confirmations.show', $bc)
            ->with('success', 'Booking Confirmation ' . $bc->number . ' berhasil dibuat.');
    }

    public function show(BookingConfirmation $bookingConfirmation)
    {
        $bookingConfirmation->load(['customer', 'job', 'creator']);

        return view('booking-confirmations.show', [
            'bc'  => $bookingConfirmation,
            'job' => $bookingConfirmation->job,
        ]);
    }

    public function edit(BookingConfirmation $bookingConfirmation)
    {
        $jobs = Job::with('customer')->latest('id')->limit(50)->get();
        $customers = Customer::orderBy('name')->get();
        $carriers = Vendor::orderBy('name')->get();
        $ports = Port::orderBy('name')->get();

        return view('booking-confirmations.edit', [
            'bc'        => $bookingConfirmation,
            'jobs'      => $jobs,
            'customers' => $customers,
            'carriers'  => $carriers,
            'ports'     => $ports,
        ]);
    }

    public function update(Request $request, BookingConfirmation $bookingConfirmation)
    {
        $validated = $request->validate([
            'number'              => 'required|string|max:60|unique:booking_confirmations,number,' . $bookingConfirmation->id,
            'booking_date'        => 'required|date',
            'job_id'              => 'nullable|exists:jobs,id',
            'customer_id'         => 'required|exists:customers,id',
            'contact_person'      => 'nullable|string|max:120',
            'customer_ref'        => 'nullable|string|max:100',
            'shipper_name'        => 'nullable|string|max:160',
            'carrier_name'        => 'nullable|string|max:160',
            'carrier_booking_no'  => 'nullable|string|max:100',
            'vessel_voyage'       => 'nullable|string|max:120',
            'service_term'        => 'required|string|max:40',
            'pol'                 => 'nullable|string|max:120',
            'pod'                 => 'nullable|string|max:120',
            'etd'                 => 'nullable|date',
            'eta'                 => 'nullable|date',
            'quantity'            => 'nullable|string|max:100',
            'cargo_description'   => 'nullable|string',
            'gross_weight'        => 'nullable|numeric|min:0',
            'volume'              => 'nullable|numeric|min:0',
            'delivery_cargo_to'   => 'nullable|string',
            'doc_cutoff_at'       => 'nullable|date',
            'cy_cutoff_at'        => 'nullable|date',
            'delivery_cutoff_at'  => 'nullable|date',
            'status'              => 'required|string|in:draft,confirmed,cancelled',
            'notes'               => 'nullable|string',
        ]);

        $bookingConfirmation->update($validated);

        return redirect()->route('booking-confirmations.show', $bookingConfirmation)
            ->with('success', 'Booking Confirmation ' . $bookingConfirmation->number . ' berhasil diperbarui.');
    }

    public function destroy(BookingConfirmation $bookingConfirmation)
    {
        $number = $bookingConfirmation->number;
        $bookingConfirmation->delete();

        return redirect()->route('booking-confirmations.index')
            ->with('success', 'Booking Confirmation ' . $number . ' berhasil dihapus.');
    }

    public function preview(BookingConfirmation $bookingConfirmation)
    {
        return view('documents.pdf-preview', [
            'title'       => 'Booking Confirmation ' . $bookingConfirmation->number,
            'backUrl'     => route('booking-confirmations.show', $bookingConfirmation),
            'pdfUrl'      => route('booking-confirmations.pdf', ['bookingConfirmation' => $bookingConfirmation, 'mode' => 'inline']),
            'downloadUrl' => route('booking-confirmations.pdf', ['bookingConfirmation' => $bookingConfirmation, 'mode' => 'download']),
        ]);
    }

    public function pdf(Request $request, BookingConfirmation $bookingConfirmation)
    {
        $bookingConfirmation->load(['customer', 'job', 'creator']);

        $pdf = app('dompdf.wrapper')->loadView('documents.pdf.booking-confirmation', [
            'bc'  => $bookingConfirmation,
            'job' => $bookingConfirmation->job,
        ])->setPaper('a4');

        $filename = 'BOOKING_CONFIRMATION_' . $bookingConfirmation->number . '.pdf';

        return $request->query('mode') === 'download'
            ? $pdf->download($filename)
            : response($pdf->output(), 200, ['Content-Type' => 'application/pdf', 'Content-Disposition' => 'inline']);
    }
}

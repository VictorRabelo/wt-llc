import { Component, ElementRef, Input, OnDestroy, OnInit, ViewChild } from '@angular/core';
import { DomSanitizer } from '@angular/platform-browser';
import { NgbActiveModal } from '@ng-bootstrap/ng-bootstrap';
import { SubSink } from 'subsink';
import { MessageService } from '@app/services/message.service';
import { ProdutoService } from '@app/services/produto.service';
import { NgForm } from '@angular/forms';

@Component({
  selector: 'app-modal-invoice',
  templateUrl: './modal-invoice.component.html',
  styleUrls: ['./modal-invoice.component.css']
})
export class ModalInvoiceComponent implements OnInit, OnDestroy {
  @ViewChild('inputProduto', {static: false}) inputProduto: ElementRef;

  private sub = new SubSink();

  loading: boolean = false;

  @Input() id: any;
  @Input() path: any;

  dados: any = {};

  constructor(
    private activeModal: NgbActiveModal,
    private service: ProdutoService,
    private message: MessageService,
    private sanitizer: DomSanitizer,
  ) {}

  ngOnInit() {}

  close(params = undefined) {
    this.activeModal.close(params);
  }
  
  handleFileInput(files: FileList) {
    
    if (files.length > 0) {
      this.dados.fileInvoice = files[0]
      
      let reader = new FileReader();
      reader.readAsDataURL(this.dados.fileInvoice);
      reader.onload = e => {
        let img =  reader.result as string;
        this.path = this.sanitizer.bypassSecurityTrustResourceUrl(img);
        this.dados.fileInvoice = img;
      }
    }
  }
  
  openPhotoPicker() {
    this.inputProduto.nativeElement.click();
  }

  download() {

  }

  expand() {
    let modal = document.querySelector('.modal-invoice');
    modal.classList.add('modal_active');  
  }

  closeFullScreen() {
    let modal = document.querySelector('.modal-invoice');
    modal.classList.remove('modal_active');
  }

  submit(form: NgForm) {
    this.loading = true;

    this.service.update(this.id, this.dados).subscribe(res => {
      this.loading = false;
      this.message.toastSuccess('Successfully Updated!');
    }, error => {
      console.log(error)
      this.message.toastError(error.message);
      this.loading = false;
    });
  }

  ngOnDestroy() {
    this.sub.unsubscribe();
  }
}

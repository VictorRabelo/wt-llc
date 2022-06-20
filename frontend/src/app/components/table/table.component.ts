import { Component, EventEmitter, Input, OnInit, Output } from '@angular/core';

import * as FileSaver from 'file-saver';
import jsPDF from 'jspdf';
import autoTable from 'jspdf-autotable';

@Component({
  selector: 'app-table',
  templateUrl: './table.component.html',
  styleUrls: ['./table.component.css']
})
export class TableComponent implements OnInit {
  
  @Output() crudTable = new EventEmitter<any>();
  @Output() isFilter = new EventEmitter<boolean>();
  
  @Input() cols: any[] = [];
  @Input() data: any[] = [];
  @Input() module: string;
  @Input() rows: number = 10;
  @Input() globalFields: any[] = [];
  
  @Input() loading: boolean = false;
  @Input() filter: boolean = true;
  
  term: string;
  itsFilter: boolean = false;
  
  exportColumns: any[];

  constructor() {}

  async ngOnInit() { 
    this.exportColumns = this.cols.map(col => ({title: col.header, dataKey: col.field}));
    this.exportColumns.pop();
  }

  crudOnTable(crud: string, id = null) {
    const response: any = { crud: crud, id: id };
    this.crudTable.emit(response);
  }
  
  openFilter(){
    this.itsFilter = this.itsFilter? false:true
    this.isFilter.emit(this.itsFilter);
  }

  exportPdf() {
    const doc = new jsPDF();
    autoTable(doc, { columns: this.exportColumns, body: this.data })
    doc.save(`${this.module}s.pdf`);
  }

  exportExcel() {
    import("xlsx").then(xlsx => {
      const worksheet = xlsx.utils.json_to_sheet(this.data);
      const workbook = { Sheets: { 'data': worksheet }, SheetNames: ['data'] };
      const excelBuffer: any = xlsx.write(workbook, { bookType: 'xlsx', type: 'array' });
      this.saveAsExcelFile(excelBuffer, this.module);
    });
  }

  saveAsExcelFile(buffer: any, fileName: string): void {
    let EXCEL_TYPE = 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet;charset=UTF-8';
    let EXCEL_EXTENSION = '.xlsx';
    const data: Blob = new Blob([buffer], { type: EXCEL_TYPE });
    FileSaver.saveAs(data, fileName + '_export_' + new Date().getTime() + EXCEL_EXTENSION);
  }
}
